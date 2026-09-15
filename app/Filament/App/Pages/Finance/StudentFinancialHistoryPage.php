<?php

namespace App\Filament\App\Pages\Finance;

use App\Filament\App\Concerns\HasCsvBulkActions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\FinancialHistoryCsvService;
use Modules\Finance\Services\FinancialHistoryExcelService;
use Modules\Finance\Services\FinanceSettingsService;
use Modules\Finance\Services\ManualFinancialEntryService;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentFinancialHistoryPage extends Page implements HasForms
{
    use HasCsvBulkActions;
    use InteractsWithForms;

    protected static string $view = 'filament.app.pages.finance.student-financial-history';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Student Financial History';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'student-financial-history';

    public ?int $student_id = null;

    public array $data = [];

    public ?int $year_id = null;

    public ?int $term_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $scope = 'active_year';

    // Filters for whole-school view
    public ?int $filter_course_id = null;

    public ?int $filter_section_id = null;

    public string $filter_payment_status = 'all';

    public ?string $filter_gender = null;

    // Pagination for the whole-school summary table
    public int $per_page = 25;

    public int $page = 1;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('finance.view_module')
            && PermissionRegistry::checkPermission('finance.view_reports');
    }

    public static function canEditFinancialHistory(): bool
    {
        return PermissionRegistry::checkPermission('finance.manage_student_financial_history');
    }

    protected static function csvService(): string
    {
        return FinancialHistoryCsvService::class;
    }

    /**
     * Header actions shown by the blade template. Only the bulk import wizard
     * lives here — the manual entry/row actions are built on demand by their
     * `*_*Action()` methods (resolved by Filament's `mountAction('name')`
     * fallback), so they never clutter the header yet stay mountable.
     */
    protected function getActions(): array
    {
        return [
            $this->makeImportAction()
                ->label(__('Import Financial History (Excel/CSV)'))
                ->modalHeading(__('Import Financial History from Excel or CSV'))
                ->modalSubmitActionLabel(__("Import Financial History"))
                ->visible(fn (): bool => static::canEditFinancialHistory()),
        ];
    }

    /**
     * Shared configuration for every manual-entry / row action. Built on
     * demand (never cached as header actions) so hidden checks are irrelevant.
     */
    protected function configureManualAction(Action $action): Action
    {
        return $action
            ->outlined()
            ->color('primary')
            ->disabled(fn (): bool => ! static::canEditFinancialHistory() || ! $this->student_id);
    }

    /** The current student, or null when in whole-school mode. */
    protected function currentStudent(): ?Student
    {
        if (! $this->student_id) {
            return null;
        }

        return Student::withoutTenantScope()->find($this->student_id);
    }

    /** Only the student's own invoices, keyed for a select. */
    protected function studentInvoiceOptions(?Student $student): array
    {
        if (! $student) {
            return [];
        }

        return Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Invoice $i) => [
                $i->id => $i->invoice_number.' — '.number_format((float) $i->balance_amount, 2).' ('.ucfirst($i->status ?? 'unpaid').')',
            ])
            ->all();
    }

    protected function studentYearOptions(): array
    {
        return AcademicYear::withoutTenantScope()
            ->where('school_id', current_tenant()?->id)
            ->orderBy('start_date')
            ->pluck('name', 'id')
            ->all();
    }

    protected function record_chargeAction(): Action
    {
        return $this->configureManualAction(Action::make('record_charge'))
            ->label(__('Record Charge'))
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->modalHeading(__('Record Charge'))
            ->modalDescription(__('Bills the student via a real invoice that flows through the invoicing engine.'))
            ->modalSubmitActionLabel(__('Record Charge'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                DatePicker::make('date')->label(__('Date'))->default(now()->toDateString())->required(),
                TextInput::make('description')->label(__('Description / Fee Name'))->placeholder('Tuition Fees')->required(),
                Select::make('academic_year_id')->label(__('Academic Year'))->options(fn () => $this->studentYearOptions()),
                Select::make('term_id')->label(__('Term'))->options(fn () => $this->studentTermOptions()),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_CHARGE, $data)
                );
                $this->sendManualSuccess(__('Charge recorded'));
            });
    }

    protected function record_paymentAction(): Action
    {
        return $this->configureManualAction(Action::make('record_payment'))
            ->label(__('Record Payment'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading(__('Record Payment'))
            ->modalDescription(__('Credits the oldest open invoice first; overpayments are carried as a student credit.'))
            ->modalSubmitActionLabel(__('Record Payment'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                DatePicker::make('date')->label(__('Date'))->default(now()->toDateString())->required(),
                Select::make('payment_method')->label(__('Payment Method'))->options([
                    'cash' => __('Cash'),
                    'bank_transfer' => __('Bank Transfer'),
                    'mobile_money' => __('Mobile Money'),
                    'card' => __('Card'),
                    'other' => __('Other'),
                ])->default('cash')->required(),
                TextInput::make('receipt_number')->label(__('Receipt Number')),
                TextInput::make('reference_number')->label(__('Reference Number')),
                Select::make('invoice_id')->label(__('Invoice (optional, FIFO by default)'))->options(fn () => $this->studentInvoiceOptions($this->currentStudent())),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_PAYMENT, $data)
                );
                $this->sendManualSuccess(__('Payment recorded'));
            });
    }

    protected function record_refundAction(): Action
    {
        return $this->configureManualAction(Action::make('record_refund'))
            ->label(__('Record Refund'))
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->modalHeading(__('Record Refund'))
            ->modalDescription(__('Reduces the paid amount on the invoice and deducts the money from the school bank balance.'))
            ->modalSubmitActionLabel(__('Record Refund'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                DatePicker::make('date')->label(__('Date'))->default(now()->toDateString())->required(),
                Select::make('invoice_id')->label(__('Invoice with payments'))->options(fn () => $this->paidInvoiceOptions($this->currentStudent()))->required(),
                Select::make('payment_method')->label(__('Payment Method'))->options([
                    'cash' => __('Cash'),
                    'bank_transfer' => __('Bank Transfer'),
                    'mobile_money' => __('Mobile Money'),
                    'card' => __('Card'),
                    'other' => __('Other'),
                ])->default('cash'),
                TextInput::make('receipt_number')->label(__('Receipt Number')),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_REFUND, $data)
                );
                $this->sendManualSuccess(__('Refund recorded'));
            });
    }

    protected function record_waiverAction(): Action
    {
        return $this->configureManualAction(Action::make('record_waiver'))
            ->label(__('Record Waiver'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('info')
            ->modalHeading(__('Record Waiver'))
            ->modalDescription(__('Applies a discount on top of any existing waiver on the invoice.'))
            ->modalSubmitActionLabel(__('Record Waiver'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                TextInput::make('description')->label(__('Waiver Name'))->placeholder('Scholarship 10%'),
                Select::make('invoice_id')->label(__('Invoice'))->options(fn () => $this->studentInvoiceOptions($this->currentStudent())),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_WAIVER, $data)
                );
                $this->sendManualSuccess(__('Waiver recorded'));
            });
    }

    protected function record_debit_carry_forwardAction(): Action
    {
        return $this->configureManualAction(Action::make('record_debit_carry_forward'))
            ->label(__('Record Debit Carry Forward'))
            ->icon('heroicon-o-arrow-up-circle')
            ->color('danger')
            ->modalHeading(__('Record Debit Carry Forward'))
            ->modalDescription(__('Carries an opening unpaid balance as a "Balance Brought Forward" invoice.'))
            ->modalSubmitActionLabel(__('Record Carry Forward'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                DatePicker::make('date')->label(__('Date'))->default(now()->toDateString())->required(),
                TextInput::make('description')->label(__('Description'))->placeholder('Balance Brought Forward'),
                Select::make('academic_year_id')->label(__('Academic Year'))->options(fn () => $this->studentYearOptions()),
                Select::make('term_id')->label(__('Term'))->options(fn () => $this->studentTermOptions()),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_DEBIT_CARRY_FORWARD, $data)
                );
                $this->sendManualSuccess(__('Debit carry forward recorded'));
            });
    }

    protected function record_credit_carry_forwardAction(): Action
    {
        return $this->configureManualAction(Action::make('record_credit_carry_forward'))
            ->label(__('Record Credit Carry Forward'))
            ->icon('heroicon-o-arrow-down-circle')
            ->color('success')
            ->modalHeading(__('Record Credit Carry Forward'))
            ->modalDescription(__('Offsets a carried-over overpayment against open invoices; any remainder is parked on the student credit balance.'))
            ->modalSubmitActionLabel(__('Record Carry Forward'))
            ->form([
                TextInput::make('amount')->label(__('Amount (USD)'))->numeric()->minValue(0.01)->required(),
                DatePicker::make('date')->label(__('Date'))->default(now()->toDateString())->required(),
                Textarea::make('notes')->label(__('Notes'))->rows(2),
            ])
            ->action(function (array $data) {
                $student = $this->currentStudent();
                ManualFinancialEntryService::persist(
                    $student,
                    ManualFinancialEntryService::row($student, FinancialHistoryCsvService::TYPE_CREDIT_CARRY_FORWARD, $data)
                );
                $this->sendManualSuccess(__('Credit carry forward recorded'));
            });
    }

    protected function edit_invoiceAction(): Action
    {
        return $this->configureManualAction(Action::make('edit_invoice'))
            ->label(__('Edit'))
            ->icon('heroicon-o-pencil')
            ->color('info')
            ->modalHeading(__('Edit Invoice'))
            ->modalSubmitActionLabel(__('Save Changes'))
            ->fillForm(fn (array $arguments): array => $this->invoiceEditDefaults($arguments))
            ->form([
                DatePicker::make('date')->label(__('Date'))->required(),
                TextInput::make('description')->label(__('Description / Fee Name')),
                TextInput::make('amount')->label(__('Amount (USD)'))
                    ->numeric()
                    ->minValue(0.01)
                    ->helperText(fn (): string => __('Amount edits are only applied when no payments or discount exist on this invoice.')),
                TextInput::make('amount_locked')->hidden(),
                Textarea::make('notes')->label(__('Edit reason (audit)'))->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $student = $this->currentStudent();
                $lock = (bool) ($data['amount_locked'] ?? false);
                unset($data['amount_locked']);
                if ($lock) {
                    unset($data['amount']);
                }
                ManualFinancialEntryService::updateInvoice($student, (int) $arguments['invoice_id'], $data, data_get($data, 'notes') ?: null);
                $this->sendManualSuccess(__('Invoice updated'));
            });
    }

    protected function invoiceEditDefaults(array $arguments): array
    {
        $invoice = null;
        if (filled($arguments['invoice_id'] ?? null)) {
            $invoice = Invoice::withoutTenantScope()->find((int) $arguments['invoice_id']);
        }

        $amountLocked = $invoice
            && (Payment::withoutTenantScope()->where('invoice_id', $invoice->id)->where('is_reversed', false)->exists()
                || (float) $invoice->discount_amount > 0
                || (float) $invoice->paid_amount > 0);

        return [
            'invoice_id' => $invoice?->id,
            'date' => $invoice?->created_at?->toDateString() ?? now()->toDateString(),
            'description' => $invoice?->items()->first()?->name ?? '',
            'amount' => $invoice?->subtotal_amount ?? '',
            'amount_locked' => $amountLocked,
            'notes' => '',
        ];
    }

    protected function delete_invoiceAction(): Action
    {
        return $this->configureManualAction(Action::make('delete_invoice'))
            ->label(__('Delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalHeading(__('Delete Invoice'))
            ->modalDescription(__('Deletes the invoice and its line items. Only possible when no payments or discount exist on it; recorded payments must be reversed first.'))
            ->requiresConfirmation()
            ->modalSubmitActionLabel(__('Delete Invoice'))
            ->modalWidth(\Filament\Support\Enums\MaxWidth::Large)
            ->form(fn (): array => [
                Textarea::make('notes')->label(__('Reason (audit)'))->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $student = $this->currentStudent();
                $invoiceId = (int) ($arguments['invoice_id'] ?? $data['invoice_id'] ?? 0);
                ManualFinancialEntryService::deleteInvoice($student, $invoiceId, data_get($data, 'notes') ?: null);
                $this->sendManualSuccess(__('Invoice deleted'));
            });
    }

    protected function reverse_paymentAction(): Action
    {
        return $this->configureManualAction(Action::make('reverse_payment'))
            ->label(__('Reverse'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalHeading(__('Reverse Payment / Refund'))
            ->modalDescription(__('Undoes the money movement, restores the bank balance and marks the entry as reversed in the ledger.'))
            ->requiresConfirmation()
            ->modalSubmitActionLabel(__('Reverse Entry'))
            ->modalWidth(\Filament\Support\Enums\MaxWidth::Large)
            ->form(fn (): array => [
                Textarea::make('notes')->label(__('Reason (audit)'))->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $student = $this->currentStudent();
                $paymentId = (int) ($arguments['payment_id'] ?? $data['payment_id'] ?? 0);
                ManualFinancialEntryService::reversePayment($student, $paymentId, data_get($data, 'notes') ?: null);
                $this->sendManualSuccess(__('Payment reversed'));
            });
    }

    protected function remove_waiverAction(): Action
    {
        return $this->configureManualAction(Action::make('remove_waiver'))
            ->label(__('Remove Waiver'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->modalHeading(__('Remove Waiver'))
            ->modalDescription(__('Zeroes the discount on this invoice so the full amount becomes payable again.'))
            ->requiresConfirmation()
            ->modalSubmitActionLabel(__('Remove Waiver'))
            ->modalWidth(\Filament\Support\Enums\MaxWidth::Large)
            ->form(fn (): array => [
                Textarea::make('notes')->label(__('Reason (audit)'))->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $student = $this->currentStudent();
                $invoiceId = (int) ($arguments['invoice_id'] ?? $data['invoice_id'] ?? 0);
                ManualFinancialEntryService::reverseWaiver($student, $invoiceId, data_get($data, 'notes') ?: null);
                $this->sendManualSuccess(__('Waiver removed'));
            });
    }

    protected function studentTermOptions(): array
    {
        return Term::withoutTenantScope()
            ->where('school_id', current_tenant()?->id)
            ->orderBy('start_date')
            ->get()
            ->mapWithKeys(fn (Term $t) => [$t->id => $t->name.($t->academicYear ? ' — '.$t->academicYear->name : '')])
            ->all();
    }

    protected function paidInvoiceOptions(?Student $student): array
    {
        if (! $student) {
            return [];
        }

        return Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Invoice $i) => [
                $i->id => $i->invoice_number.' — paid '.number_format((float) $i->paid_amount, 2),
            ])
            ->all();
    }

    protected function sendManualSuccess(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return __('Student Financial History');
    }

    public static function getNavigationLabel(): string
    {
        return __('Student Financial History');
    }

    public function mount(): void
    {
        $this->student_id = (int) request()->query('student', $this->student_id ?? 0) ?: null;

        $this->year_id = $this->year_id
            ?? AcademicYear::withoutTenantScope()
                ->where('school_id', current_tenant()?->id)
                ->where('is_active', true)
                ->value('id');

        $this->form->fill(['student_id' => $this->student_id]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label(__('Select Student'))
                    ->searchable()
                    ->preload()
                    ->placeholder(__('Search and select a student...'))
                    ->options(fn () => Student::withoutTenantScope()
                        ->where('school_id', current_tenant()?->id)
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (Student $s) => [
                            $s->id => trim($s->full_name.' ('.($s->student_id_number ?: ($s->admission_number ?? '')).')'),
                        ]))
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->student_id = (int) $state ?: null;
                    }),
            ])
            ->statePath('data');
    }

    public function selectStudent(int $studentId): void
    {
        $this->student_id = $studentId;
        $this->data['student_id'] = $studentId;
    }

    public function backToSchoolSummary(): void
    {
        $this->student_id = null;
        $this->data['student_id'] = null;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function restoreDefaults(): void
    {
        $activeYear = AcademicYear::withoutTenantScope()
            ->where('school_id', current_tenant()?->id)
            ->where('is_active', true)
            ->first();

        $this->year_id = $activeYear?->id;
        $this->term_id = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->scope = 'active_year';
        $this->filter_course_id = null;
        $this->filter_section_id = null;
        $this->filter_payment_status = 'all';
        $this->filter_gender = null;
    }

    protected function scopeDates(): array
    {
        $start = $this->start_date;
        $end = $this->end_date;

        if ($this->scope !== 'custom') {
            $start = null;
            $end = null;
        }

        return StudentFinancialHistoryService::scopeDates(
            current_tenant()?->id,
            $this->scope === 'full' ? null : $this->year_id,
            $this->scope === 'term' ? $this->term_id : null,
            $start,
            $end,
        );
    }

    /**
     * Build the summary data for the whole-school view (no student selected).
     */
    protected function buildSchoolSummary(): array
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $query = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['currentEnrollment.course', 'currentEnrollment.section']);

        // Apply filters
        if ($this->filter_course_id) {
            $query->whereHas('currentEnrollment', fn ($q) => $q->where('course_id', $this->filter_course_id));
        }

        if ($this->filter_section_id) {
            $query->whereHas('currentEnrollment', fn ($q) => $q->where('section_id', $this->filter_section_id));
        }

        if ($this->filter_gender && $this->filter_gender !== 'all') {
            $query->where('gender', $this->filter_gender);
        }

        $students = $query->orderBy('last_name')->get();

        $summaries = [];
        foreach ($students as $student) {
            $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

            $billed = $ledger['total_billed'];
            $paid = $ledger['total_paid'];
            $balance = $ledger['closing_balance'];

            // Payment status filter
            if ($this->filter_payment_status === 'paid' && $balance > 0.01) {
                continue;
            }
            if ($this->filter_payment_status === 'unpaid' && $balance < 0.01) {
                continue;
            }

            $summaries[] = [
                'student' => $student,
                'student_id' => $student->student_id_number,
                'admission_number' => $student->admission_number,
                'gender' => $student->gender,
                'class' => trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')) ?: 'Unassigned',
                'billed' => $billed,
                'paid' => $paid,
                'balance' => $balance,
                'status' => $balance > 0.01 ? ($paid > 0.01 ? 'partial' : 'unpaid') : 'paid',
            ];
        }

        $totalBilled = array_sum(array_column($summaries, 'billed'));
        $totalPaid = array_sum(array_column($summaries, 'paid'));
        $totalBalance = array_sum(array_column($summaries, 'balance'));

        $totalCount = count($summaries);
        $perPage = max(1, $this->per_page);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $this->page = min(max(1, $this->page), $totalPages);
        $currentPage = $this->page;
        $pageSummaries = array_slice($summaries, ($currentPage - 1) * $perPage, $perPage);

        return [
            'summaries' => $pageSummaries,
            'count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'courses' => Course::where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id'),
            'sections' => Section::whereHas('course', fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $data = [
            'student' => null,
            'ledger' => [],
            'years' => AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->orderBy('name')->get(),
            'terms' => Term::withoutTenantScope()
                ->where('school_id', $schoolId)
                ->when($this->year_id, fn ($q) => $q->where('academic_year_id', $this->year_id))
                ->with('academicYear')
                ->orderBy('start_date')
                ->get(),
            'start' => $scope['start'],
            'end' => $scope['end'],
            'label' => $scope['label'],
            'can_edit' => static::canEditFinancialHistory(),
            'billing_frequency' => FinanceSettingsService::billingFrequency((int) $schoolId),
        ];

        if (! $this->student_id) {
            return array_merge($data, $this->buildSchoolSummary());
        }

        $student = Student::withoutTenantScope()
            ->with(['currentEnrollment.course', 'currentEnrollment.section'])
            ->find($this->student_id);

        if (! $student || $student->school_id !== $schoolId) {
            return array_merge($data, $this->buildSchoolSummary());
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        return array_merge($data, [
            'student' => $student,
            'ledger' => $ledger,
        ]);
    }

    public function downloadCsv(): StreamedResponse
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $student = $this->student_id
            ? Student::withoutTenantScope()->with(['currentEnrollment.course', 'currentEnrollment.section'])->find($this->student_id)
            : null;

        if (! $student || $student->school_id !== $schoolId) {
            abort(404);
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        $rows = [];
        $rows[] = ['Student Financial History'];
        $rows[] = ['Student', $student->full_name];
        $rows[] = ['Admission No.', $student->admission_number];
        $rows[] = ['Class', trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? ''))];
        $rows[] = ['Enrolled', $student->admission_date?->toDateString()];
        $rows[] = ['Period', $scope['start']?->toDateString() ?? 'From enrolment', 'to', $scope['end']?->toDateString() ?? 'today'];
        $rows[] = [];
        $rows[] = ['Date', 'Description', 'Receipt', 'Reference', 'Method', 'Debit ($)', 'Credit ($)', 'Balance ($)', 'Received By'];

        foreach ($ledger['rows'] as $row) {
            $rows[] = [
                ($row['date'] instanceof Carbon ? $row['date']->toDateString() : ''),
                $row['description'],
                $row['receipt'] ?? '',
                $row['reference'] ?? '',
                $row['method'] ?? '',
                number_format($row['debit'], 2, '.', ''),
                number_format($row['credit'], 2, '.', ''),
                number_format($row['running_balance'], 2, '.', ''),
                $row['received_by'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Opening Balance', number_format($ledger['opening_balance'], 2)];
        $rows[] = ['Total Billed', number_format($ledger['total_billed'], 2)];
        $rows[] = ['Total Paid', number_format($ledger['total_paid'], 2)];
        $rows[] = ['Total Refunded', number_format($ledger['total_refunded'], 2)];
        $rows[] = ['Closing Balance', number_format($ledger['closing_balance'], 2)];

        $safeAdm = str_replace(['/', '\\'], '_', $student->admission_number);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);
        }, 'Financial_History_'.$safeAdm.'_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function downloadExcel(): StreamedResponse
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $student = $this->student_id
            ? Student::withoutTenantScope()->with(['currentEnrollment.course', 'currentEnrollment.section'])->find($this->student_id)
            : null;

        if (! $student || $student->school_id !== $schoolId) {
            abort(404);
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        return FinancialHistoryExcelService::downloadStatement($ledger, $student, $scope);
    }

    public function downloadBulkExcel(): StreamedResponse
    {
        $scope = $this->scopeDates();

        $data = $this->buildSchoolSummary();

        return FinancialHistoryExcelService::downloadSummary($data, $scope);
    }

    public function downloadBulkCsv(): StreamedResponse
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $data = $this->buildSchoolSummary();

        $rows = [];
        $rows[] = ['Student Financial History — Bulk Export'];
        $rows[] = ['Period', $scope['start']?->toDateString() ?? 'From enrolment', 'to', $scope['end']?->toDateString() ?? 'today'];
        $rows[] = [];
        $rows[] = ['Student Name', 'Admission No', 'Class', 'Gender', 'Total Billed ($)', 'Total Paid ($)', 'Balance ($)', 'Status'];

        foreach ($data['summaries'] as $s) {
            $rows[] = [
                trim($s['student']->full_name),
                $s['admission_number'] ?? '',
                $s['class'],
                ucfirst($s['gender'] ?? ''),
                number_format($s['billed'], 2, '.', ''),
                number_format($s['paid'], 2, '.', ''),
                number_format($s['balance'], 2, '.', ''),
                match ($s['status']) {
                    'paid' => 'Paid', 'partial' => 'Partially Paid', default => 'Unpaid'
                },
            ];
        }

        $rows[] = [];
        $rows[] = ['TOTALS', '', '', '', number_format($data['total_billed'], 2), number_format($data['total_paid'], 2), number_format($data['total_balance'], 2)];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);
        }, 'Financial_History_Bulk_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
