<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\InvoiceResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\FeeWaiver;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\FinancialSecurityService;
use Modules\Finance\Services\InvoicingService;
use Modules\Students\Models\Student;

class InvoiceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Student Invoices';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 4;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manual Invoice Details')
                    ->description(__('Select the student first to automatically calculate their term fees.'))
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} ".($record->last_name ?? ''))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateManualInvoiceAmount($set, $get))
                            ->rules([
                                fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $yearId = $get('academic_year_id');
                                    $termId = $get('term_id');
                                    if (! $yearId || ! $termId || ! $value) {
                                        return;
                                    }

                                    $exists = Invoice::where([
                                        'student_id' => $value,
                                        'academic_year_id' => $yearId,
                                        'term_id' => $termId,
                                    ])->exists();

                                    if ($exists) {
                                        $fail('An invoice has already been generated for this student for the selected term and year.');
                                    }
                                },
                            ]),

                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Academic Year'))
                            ->options(function () {
                                return AcademicYear::where('is_active', true)->pluck('name', 'id');
                            })
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateManualInvoiceAmount($set, $get)),

                        Forms\Components\Select::make('term_id')
                            ->label(__('Term'))
                            ->options(function (Forms\Get $get) {
                                $yearId = $get('academic_year_id') ?? AcademicYear::where('is_active', true)->first()?->id;
                                if (! $yearId) {
                                    return [];
                                }

                                return Term::where('academic_year_id', $yearId)
                                    ->get()
                                    ->mapWithKeys(function ($t) {
                                        return [$t->id => ucwords(strtolower($t->name))];
                                    });
                            })
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateManualInvoiceAmount($set, $get))
                            ->placeholder(__('Select Term...')),

                        Forms\Components\Select::make('fee_waiver_id')
                            ->label(__('Apply Specific Waiver / Scholarship'))
                            ->options(FeeWaiver::all()->pluck('name', 'id'))
                            ->placeholder(__('None / No Scholarship')),

                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->default(fn () => 'INV-'.date('Y').'-'.mt_rand(1000, 9999)),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->label(__('Billing Amount ($)'))
                            ->helperText('This value automatically calculates based on the selected student\'s configured cohort fees.'),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addMonth()),

                        // FIX: Added missing hidden fields so manual saves capture the waiver breakdown
                        Forms\Components\Hidden::make('subtotal_amount')->default(0.00),
                        Forms\Components\Hidden::make('discount_amount')->default(0.00),
                        Forms\Components\Hidden::make('balance_amount')->default(0.00),
                        Forms\Components\Hidden::make('waiver_details')->default(null),
                    ])->columns(2),
            ]);
    }

    public static function recalculateManualInvoiceAmount(Forms\Set $set, Forms\Get $get)
    {
        $studentId = $get('student_id');
        $yearId = $get('academic_year_id');
        $termId = $get('term_id');

        if (! $studentId || ! $yearId || ! $termId) {
            return;
        }

        $student = Student::with(['currentEnrollment.course', 'waivers'])->find($studentId);
        if (! $student || ! $student->currentEnrollment) {
            return;
        }

        $course = $student->currentEnrollment->course;
        $courseName = strtolower($course->name);
        $schoolId = app('current_tenant')->id;

        $applicableScopes = ['all', 'single'];

        if (preg_match('/form\s*[1-4]/i', $courseName) || preg_match('/grade\s*[8-9]/i', $courseName)) {
            $applicableScopes[] = 'form_1_4';
        } elseif (preg_match('/form\s*[5-6]/i', $courseName) || preg_match('/six/i', $courseName)) {
            $applicableScopes[] = 'form_5_6';
        } elseif (preg_match('/ecd/i', $courseName) || preg_match('/infant/i', $courseName)) {
            $applicableScopes[] = 'ecd';
        } elseif (preg_match('/grade\s*[1-7]/i', $courseName)) {
            $applicableScopes[] = 'grade_1_7';
        }

        $feeStructures = FeeStructure::where([
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'term_id' => $termId,
        ])
            ->where(function ($q) use ($applicableScopes, $course) {
                $q->whereIn('scope_type', $applicableScopes)
                    ->where(function ($sub) use ($course) {
                        $sub->where('scope_type', '!=', 'single')
                            ->orWhere('course_id', $course->id);
                    });
            })->get();

        $subtotal = $feeStructures->sum('amount');

        // Apply and trace active waivers
        $discount = 0;
        $waiverDetailsString = null;
        $waiver = $student->waivers->first();
        if ($waiver) {
            if ($waiver->type === 'percentage') {
                $discount = ($subtotal * ($waiver->value / 100));
                $waiverDetailsString = "{$waiver->name} ({$waiver->value}% - \$".number_format($discount, 2).')';
            } else {
                $discount = $waiver->value;
                $waiverDetailsString = "{$waiver->name} (Fixed - \$".number_format($discount, 2).')';
            }
        }

        $total = max(0, $subtotal - $discount);

        // FIX: Reconciled all database columns
        $set('total_amount', $total);
        $set('subtotal_amount', $subtotal);
        $set('discount_amount', $discount);
        $set('balance_amount', $total);
        $set('waiver_details', $waiverDetailsString);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('student.photo_path')
                    ->label(__('Photo'))
                    ->disk('public')
                    ->getStateUsing(fn ($record) => resolve_public_asset_path($record->student?->photo_path))
                    ->circular()
                    ->defaultImageUrl(url('images/student_avatar.png'))
                    ->size(40)
                    ->limit(50),
                Tables\Columns\TextColumn::make('invoice_number')->searchable(),
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student'))
                    ->formatStateUsing(fn ($record) => "{$record->student->first_name} ".($record->student->last_name ?? ''))
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.currentEnrollment.section.name')
                    ->label(__('Class'))
                    ->formatStateUsing(fn ($record) => ($record->student->currentEnrollment?->course?->name ?? '').' '.($record->student->currentEnrollment?->section?->name ?? '')),
                Tables\Columns\TextColumn::make('term.name')
                    ->label(__('Term'))
                    ->formatStateUsing(fn ($state) => ucwords(strtolower($state))),
                Tables\Columns\TextColumn::make('total_amount')->money('USD'),
                Tables\Columns\TextColumn::make('paid_amount')->money('USD')->color('success'),
                Tables\Columns\TextColumn::make('balance_amount')->money('USD')->color('danger'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waiver_details')
                    ->label(__('Waiver'))
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->color('success')
                    ->wrap()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->waiver_details ?: 'No waiver applied'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Grade / Form Level'))
                    ->relationship('student.currentEnrollment.course', 'name'),

                Tables\Filters\SelectFilter::make('section_id')
                    ->label(__('Class Stream'))
                    ->relationship('student.currentEnrollment.section', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unpaid' => __('Unpaid'),
                        'partially_paid' => __('Partially Paid'),
                        'paid' => __('Paid In Full'),
                    ]),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label(__('Academic Year'))
                    ->relationship('term.academicYear', 'name'),

                Tables\Filters\SelectFilter::make('term_id')
                    ->label(__('Term'))
                    ->options(function () {
                        $activeYear = AcademicYear::where('is_active', true)->first();
                        if (! $activeYear) {
                            return [];
                        }

                        return Term::where('academic_year_id', $activeYear->id)
                            ->get()
                            ->mapWithKeys(function ($term) {
                                return [$term->id => ucwords(strtolower($term->name))];
                            });
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulkInvoiceRun')
                    ->label(__('Run Auto-Billing Engine'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('billing_scope')
                            ->label(__('Billing Target'))
                            ->options([
                                'school' => __('Whole School (All Students)'),
                                'form' => __('Grade / Form Level (e.g. All Form 1s)'),
                                'stream' => __('Specific Class Stream (e.g. Form 1A only)'),
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('course_id')
                            ->label(__('Form Level'))
                            ->options(Course::all()->pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => $get('billing_scope') !== 'school')
                            ->required(fn (Forms\Get $get) => $get('billing_scope') !== 'school')
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Class Stream'))
                            ->options(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (! $courseId) {
                                    return [];
                                }

                                return Section::where('course_id', $courseId)->pluck('name', 'id');
                            })
                            ->visible(fn (Forms\Get $get) => $get('billing_scope') === 'stream')
                            ->required(fn (Forms\Get $get) => $get('billing_scope') === 'stream'),

                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Year'))
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return AcademicYear::orderBy('start_date', 'desc')->pluck('name', 'id');
                            })
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                            ->required(),

                        Forms\Components\Select::make('term_id')
                            ->label(__('Term'))
                            ->options(function (Forms\Get $get) {
                                $yearId = $get('academic_year_id') ?? AcademicYear::where('is_active', true)->first()?->id;
                                if (! $yearId) {
                                    return [];
                                }

                                return Term::where('academic_year_id', $yearId)
                                    ->get()
                                    ->mapWithKeys(fn ($t) => [$t->id => ucwords(strtolower($t->name))]);
                            })
                            ->required(),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addMonth()),
                    ])
                    ->action(function (array $data, InvoicingService $service) {
                        $result = $service->runInvoicingEngine(
                            $data['billing_scope'],
                            $data['academic_year_id'],
                            $data['term_id'],
                            $data['due_date'],
                            $data['course_id'] ?? null,
                            $data['section_id'] ?? null
                        );

                        $lines = [];
                        $lines[] = 'Generated '.$result['generated'].' invoice(s).';
                        if (($result['scanned'] ?? 0) > 0) {
                            $lines[] = 'Scanned '.$result['scanned'].' enrolled student(s).';
                        }
                        if (($result['no_fee_structure'] ?? 0) > 0) {
                            $lines[] = $result['no_fee_structure'].' skipped: no Fee Structure configured for this academic year + term.';
                        }
                        if (($result['already_billed'] ?? 0) > 0) {
                            $lines[] = $result['already_billed'].' already billed for this year + term (no duplicates created).';
                        }
                        if (($result['missing_data'] ?? 0) > 0) {
                            $lines[] = $result['missing_data'].' skipped: student/course record missing.';
                        }
                        if (($result['no_enrollment_match'] ?? 0) > 0) {
                            $lines[] = $result['no_enrollment_match'].' students had no enrollment for the selected year/scope.';
                        }

                        if ($result['generated'] > 0) {
                            Notification::make()
                                ->title(__('Invoicing Engine Complete'))
                                ->body(implode(' ', $lines))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('No invoices generated'))
                                ->body(implode(' ', $lines))
                                ->warning()
                                ->send();
                        }
                    }),

                // IMPROVED: Added full options dialog to printFiltered header action (Combined PDF or ZIP download)
                Tables\Actions\Action::make('printFiltered')
                    ->label(__('Print All Filtered'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('print_mode')
                            ->label(__('Select Output Type'))
                            ->options([
                                'combined' => __('Single Combined PDF (Best for printing)'),
                                'zip' => __('ZIP Archive (Individual PDF files)'),
                            ])
                            ->default('combined')
                            ->required(),
                        Forms\Components\Select::make('print_type')
                            ->label(__('Document Type'))
                            ->options([
                                'invoices' => __('Invoices'),
                                'receipts' => __('Receipts (Paid Only)'),
                                'statements' => __('Statements of Account'),
                            ])
                            ->default('invoices')
                            ->required(),
                    ])
                    ->action(function (array $data, Pages\ListInvoices $livewire) {
                        $query = $livewire->getFilteredTableQuery();

                        if ($data['print_type'] === 'receipts') {
                            $query->where('paid_amount', '>', 0);
                        }

                        $ids = $query->pluck('id')->toArray();

                        if (count($ids) === 0) {
                            Notification::make()
                                ->title(__('No Records Found'))
                                ->warning()
                                ->send();

                            return;
                        }

                        return redirect()->route('invoices.bulk-pdf', [
                            'ids' => implode(',', $ids),
                            'mode' => $data['print_mode'],
                            'type' => $data['print_type'],
                        ], false);
                    }),
                Tables\Actions\Action::make('exportCsv')
                    ->label(__('Export CSV'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Pages\ListInvoices $livewire) {
                        $records = $livewire->getFilteredTableQuery()
                            ->with(['student.currentEnrollment.course', 'student.currentEnrollment.section', 'term'])
                            ->get();

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title(__('No Records Found'))
                                ->warning()
                                ->send();

                            return;
                        }

                        $headers = [
                            'Invoice No',
                            'Student Name',
                            'Admission No',
                            'Class',
                            'Term',
                            'Total ($)',
                            'Paid ($)',
                            'Balance ($)',
                            'Status',
                            'Waiver',
                            'Date',
                        ];

                        $rows = $records->map(fn ($r) => [
                            $r->invoice_number,
                            trim(($r->student->first_name ?? '').' '.($r->student->last_name ?? '')),
                            $r->student->admission_number ?? '',
                            trim(($r->student->currentEnrollment?->course?->name ?? '').' '.($r->student->currentEnrollment?->section?->name ?? '')),
                            ucwords(strtolower($r->term->name ?? '')),
                            number_format((float) $r->total_amount, 2, '.', ''),
                            number_format((float) $r->paid_amount, 2, '.', ''),
                            number_format((float) $r->balance_amount, 2, '.', ''),
                            ucfirst(str_replace('_', ' ', $r->status)),
                            $r->waiver_details ?? '',
                            $r->created_at?->format('Y-m-d') ?? '',
                        ]);

                        $csv = fopen('php://temp', 'r+');
                        fputcsv($csv, $headers);
                        foreach ($rows as $row) {
                            fputcsv($csv, $row);
                        }
                        rewind($csv);

                        $content = stream_get_contents($csv);
                        fclose($csv);

                        $filename = 'Invoices_Export_'.now()->format('Y-m-d_His').'.csv';

                        return response()->streamDownload(function () use ($content) {
                            echo $content;
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('recordPayment')
                    ->label(__('Record Payment'))
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')->numeric()->required(),
                        Forms\Components\Select::make('currency')->options(['USD' => 'USD', 'ZiG' => 'ZiG'])->default('USD')->required(),
                        Forms\Components\Select::make('payment_method')->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer',
                            'Ecocash' => 'EcoCash',
                            'zipit' => 'ZIPIT / RTGS',
                        ])->required(),
                        Forms\Components\TextInput::make('reference_number')->required()->label(__('TXN Reference Number')),
                    ])
                    ->action(function (Invoice $record, array $data, ExchangeRateService $rateService, FinancialSecurityService $securityService) {
                        if ($securityService->detectDuplicatePaymentReference($record->school_id, $data['reference_number'])) {
                            Notification::make()
                                ->title(__('Duplicate Payment Detected'))
                                ->body('The reference number entered already exists inside the school ledger system.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $amt = $data['amount'];
                        if ($data['currency'] === 'ZiG') {
                            $amt = $rateService->convertToUSD($amt);
                        }

                        Payment::create([
                            'school_id' => $record->school_id,
                            'invoice_id' => $record->id,
                            'receipt_number' => 'RCP-'.mt_rand(10000, 99999),
                            'reference_number' => $data['reference_number'],
                            'amount' => $amt,
                            'currency' => 'USD',
                            'payment_method' => $data['payment_method'],
                            'payment_date' => now(),
                        ]);

                        $record->paid_amount += $amt;
                        $record->balance_amount = max(0, $record->total_amount - $record->paid_amount);
                        $record->status = $record->balance_amount <= 0 ? 'paid' : 'partially_paid';
                        $record->save();
                    }),

                Tables\Actions\Action::make('printInvoice')
                    ->icon('heroicon-o-document-text')
                    ->iconButton()
                    ->tooltip(__('Print Invoice'))
                    ->url(fn ($record) => route('invoice.pdf', ['record' => $record->id], false))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('printReceipt')
                    ->icon('heroicon-o-receipt-percent')
                    ->iconButton()
                    ->tooltip(__('Print Receipt'))
                    ->url(fn ($record) => route('receipt.pdf', ['record' => $record->id], false))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->paid_amount > 0),

                Tables\Actions\Action::make('printStatement')
                    ->icon('heroicon-o-book-open')
                    ->iconButton()
                    ->tooltip(__('Print Statement of Account'))
                    ->url(fn ($record) => route('statement.pdf', ['record' => $record->id], false))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('adjustFee')
                    ->label(__('Adjust Fee'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Section::make(__('Fee Adjustment'))
                            ->description(__('Adjust the total amount for this invoice. This modifies the official fee charged.'))
                            ->schema([
                                Forms\Components\TextInput::make('new_total')
                                    ->label(__('New Total Amount (USD)'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->helperText(fn ($record) => __('Current total: $').number_format((float) $record->total_amount, 2)),
                                Forms\Components\Textarea::make('adjustment_reason')
                                    ->label(__('Reason for Adjustment'))
                                    ->required()
                                    ->placeholder(__('e.g. Fee waiver approved, sibling discount, admin correction'))
                                    ->rows(3),
                            ]),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $oldTotal = (float) $record->total_amount;
                        $newTotal = (float) $data['new_total'];

                        if ($newTotal === $oldTotal) {
                            return;
                        }

                        $record->update([
                            'subtotal_amount' => $newTotal,
                            'discount_amount' => 0,
                            'total_amount' => $newTotal,
                        ]);

                        Notification::make()
                            ->title(__('Fee Adjusted'))
                            ->body(__('Invoice :number adjusted from $:old to $:new. Reason: :reason', [
                                'number' => $record->invoice_number,
                                'old' => number_format($oldTotal, 2),
                                'new' => number_format($newTotal, 2),
                                'reason' => $data['adjustment_reason'],
                            ]))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => ! $record->is_locked),

                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label(__('Print Selected Invoices'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('print_mode')
                                ->label(__('Select Output Type'))
                                ->options([
                                    'combined' => __('Single Combined PDF (Best for printing)'),
                                    'zip' => __('ZIP Archive (Individual PDF files)'),
                                ])
                                ->default('combined')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $ids = $records->pluck('id')->join(',');

                            return redirect()->route('invoices.bulk-pdf', [
                                'ids' => $ids,
                                'mode' => $data['print_mode'],
                                'type' => 'invoices',
                            ], false);
                        }),

                    Tables\Actions\BulkAction::make('printReceipts')
                        ->label(__('Print Selected Receipts'))
                        ->icon('heroicon-o-receipt-percent')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('print_mode')
                                ->label(__('Select Output Type'))
                                ->options([
                                    'combined' => __('Single Combined PDF (Best for printing)'),
                                    'zip' => __('ZIP Archive (Individual PDF files)'),
                                ])
                                ->default('combined')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $paid = $records->filter(fn ($r) => $r->paid_amount > 0);

                            if ($paid->isEmpty()) {
                                Notification::make()
                                    ->title(__('No Paid Invoices Selected'))
                                    ->body(__('Only paid invoices have receipts to print.'))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            return redirect()->route('invoices.bulk-pdf', [
                                'ids' => $paid->pluck('id')->join(','),
                                'mode' => $data['print_mode'],
                                'type' => 'receipts',
                            ], false);
                        }),

                    Tables\Actions\BulkAction::make('printStatements')
                        ->label(__('Print Selected Statements'))
                        ->icon('heroicon-o-book-open')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('print_mode')
                                ->label(__('Select Output Type'))
                                ->options([
                                    'combined' => __('Single Combined PDF (Best for printing)'),
                                    'zip' => __('ZIP Archive (Individual PDF files)'),
                                ])
                                ->default('combined')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            return redirect()->route('invoices.bulk-pdf', [
                                'ids' => $records->pluck('id')->join(','),
                                'mode' => $data['print_mode'],
                                'type' => 'statements',
                            ], false);
                        }),

                    Tables\Actions\BulkAction::make('deleteSelected')
                        ->label(__('Delete Selected'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('Delete selected invoices?'))
                        ->modalDescription('Locked invoices (historical audit protection) will be skipped. This permanently removes the selected invoices and their payments and cannot be undone.')
                        ->action(function (Collection $records, FinancialSecurityService $security) {
                            $locked = $records->filter(fn ($r) => $r->is_locked);
                            $deletable = $records->filter(fn ($r) => ! $r->is_locked);

                            foreach ($deletable as $record) {
                                $security->logTransaction('invoice_bulk_deleted', $record, $record->toArray(), null);
                                $record->delete();
                            }

                            $deletedCount = $deletable->count();
                            $lockedCount = $locked->count();

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->title("Deleted {$deletedCount} invoice(s)")
                                    ->success()
                                    ->send();
                            }

                            if ($lockedCount > 0) {
                                Notification::make()
                                    ->title("Skipped {$lockedCount} locked invoice(s)")
                                    ->body('Locked invoices cannot be deleted because they are protected for historical auditing.')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->content(fn () => view('filament.app.resources.invoice.invoice-cards'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'student.currentEnrollment.course',
                'student.currentEnrollment.section',
                'term',
            ]))
            ->paginated([8, 16, 24, 48, 'all'])
            ->defaultPaginationPageOption(8);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
