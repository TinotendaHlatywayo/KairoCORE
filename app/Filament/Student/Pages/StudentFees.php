<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\Finance\Services\StudentFeePaymentService;
use Modules\SaaS\Gateways\GatewayPayload;
use Modules\SaaS\Services\GatewayResolver;
use Modules\Students\Models\Student;

class StudentFees extends Page
{
    use WithFileUploads;

    protected static string $view = 'filament.student.pages.student-fees';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Fees & Payments';

    protected static ?string $navigationLabel = 'My Fees';

    protected static ?string $title = 'My Fees';

    protected static ?string $slug = 'my-fees';

    public static function getNavigationLabel(): string
    {
        return __('My Fees');
    }

    // Term selector
    public ?int $selectedTermId = null;

    // Bank deposit form properties
    public ?int $selectedInvoiceId = null;

    public string $refNumber = '';

    public float $payAmount = 0.00;

    public string $payDate = '';

    public string $sourceBankName = '';

    public string $sourceAccountNumber = '';

    public ?int $selectedBankAccountId = null;

    public string $notes = '';

    public ?TemporaryUploadedFile $uploadedReceiptFile = null;

    public function mount(): void
    {
        $this->pollPendingTransactions();

        // Set default to current term
        $student = $this->getStudent();
        if ($student) {
            $activeYear = AcademicYear::where('school_id', $student->school_id)->where('is_active', true)->first();
            if ($activeYear) {
                $currentTerm = Term::where('academic_year_id', $activeYear->id)->where('school_id', $student->school_id)
                    ->where('start_date', '<=', now())->where('end_date', '>=', now())->first();
                if ($currentTerm) {
                    $this->selectedTermId = $currentTerm->id;
                }
            }
        }
    }

    protected function pollPendingTransactions(): void
    {
        $student = $this->getStudent();
        if (! $student) {
            return;
        }

        $pending = StudentPaymentSubmission::where('student_id', $student->id)
            ->where('status', StudentPaymentSubmission::STATUS_PENDING)
            ->where('gateway', 'paynow')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        $gateway = app(GatewayResolver::class)->resolve('paynow');

        foreach ($pending as $submission) {
            if (! $submission->transaction_reference) {
                continue;
            }

            try {
                $response = $gateway->verifyPayment($submission->transaction_reference);

                if ($response->isSuccess) {
                    StudentFeePaymentService::creditSubmission($submission);

                    Notification::make()
                        ->title(__('Payment Verified Successfully'))
                        ->body(__('Your fee payment has been credited to your account.'))
                        ->success()
                        ->send();
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function initializeOnlinePayment(int $invoiceId): void
    {
        $invoice = $this->getStudentInvoice($invoiceId);

        if (! $invoice) {
            Notification::make()
                ->title(__('Invoice Not Found'))
                ->danger()
                ->send();

            return;
        }

        if ($invoice->balance_amount <= 0) {
            Notification::make()
                ->title(__('Invoice Already Settled'))
                ->body(__('This invoice has no outstanding balance.'))
                ->warning()
                ->send();

            return;
        }

        try {
            $gateway = app(GatewayResolver::class)->resolve('paynow', [
                'return_url' => route('filament.student.pages.my-fees'),
                'result_url' => route('saas.paynow.webhook'),
            ]);

            $payload = new GatewayPayload(
                amount: (float) $invoice->balance_amount,
                currency: 'USD',
                invoiceNumber: $invoice->invoice_number,
                successUrl: route('filament.student.pages.my-fees'),
                cancelUrl: route('filament.student.pages.my-fees'),
                metaData: [
                    'email' => Auth::user()->email,
                    'description' => 'School Fee Payment: '.$invoice->invoice_number,
                ]
            );

            $response = $gateway->initializePayment($payload);

            if ($response->isSuccess && $response->redirectUrl) {
                StudentPaymentSubmission::create([
                    'school_id' => $invoice->school_id,
                    'invoice_id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'gateway' => 'paynow',
                    'amount' => $invoice->balance_amount,
                    'currency' => 'USD',
                    'status' => StudentPaymentSubmission::STATUS_PENDING,
                    'transaction_reference' => $response->transactionReference,
                ]);

                $this->dispatch('open-paynow-tab', url: $response->redirectUrl);
            } else {
                Notification::make()
                    ->title(__('Payment Initialization Failed'))
                    ->body($response->errorMessage ?: __('Could not connect to Paynow. Please try again.'))
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Payment Initialization Failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitManualProof(): void
    {
        $this->validate([
            'selectedInvoiceId' => 'required|exists:invoices,id',
            'refNumber' => 'required|string|max:100',
            'payAmount' => 'required|numeric|min:0.01',
            'payDate' => 'required|date',
            'sourceBankName' => 'required|string|max:255',
            'selectedBankAccountId' => 'nullable|exists:school_bank_accounts,id',
            'uploadedReceiptFile' => 'required|file|max:5120|mimes:jpeg,png,pdf',
        ]);

        $student = $this->getStudent();
        if (! $student) {
            Notification::make()
                ->title(__('Student Record Not Found'))
                ->danger()
                ->send();

            return;
        }

        $invoice = Invoice::where('id', $this->selectedInvoiceId)->where('student_id', $student->id)->first();
        if (! $invoice) {
            Notification::make()
                ->title(__('Invalid Invoice'))
                ->danger()
                ->send();

            return;
        }

        $filePath = $this->uploadedReceiptFile->store('student_receipts_proofs', 'public');

        StudentPaymentSubmission::create([
            'school_id' => $student->school_id,
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'gateway' => 'manual',
            'reference_number' => $this->refNumber,
            'amount' => $this->payAmount,
            'currency' => 'USD',
            'payment_date' => $this->payDate,
            'bank_name' => $this->sourceBankName,
            'source_bank_name' => $this->sourceBankName,
            'source_account_number' => $this->sourceAccountNumber,
            'destination_bank_account_id' => $this->selectedBankAccountId,
            'notes' => $this->notes,
            'proof_file_path' => $filePath,
            'status' => StudentPaymentSubmission::STATUS_PENDING,
        ]);

        // Notify school admin/accounting
        StudentFeePaymentService::notifySchoolOfNewSubmission(
            StudentPaymentSubmission::where('student_id', $student->id)
                ->where('invoice_id', $invoice->id)
                ->latest()
                ->first()
        );

        Notification::make()
            ->title(__('Receipt Uploaded Successfully'))
            ->body(__('The finance office will review and approve your submission shortly.'))
            ->success()
            ->send();

        $this->reset([
            'selectedInvoiceId', 'refNumber', 'payAmount', 'payDate',
            'sourceBankName', 'sourceAccountNumber', 'selectedBankAccountId',
            'notes', 'uploadedReceiptFile',
        ]);
    }

    protected function getStudent(): ?Student
    {
        return HomeworkResource::currentStudent();
    }

    protected function getStudentInvoice(int $invoiceId): ?Invoice
    {
        $student = $this->getStudent();
        if (! $student) {
            return null;
        }

        return Invoice::where('id', $invoiceId)->where('student_id', $student->id)->first();
    }

    protected function getViewData(): array
    {
        $student = $this->getStudent();
        $schoolId = $student?->school_id;

        // Get available terms
        $terms = collect();
        $allInvoices = collect();
        if ($schoolId) {
            $terms = Term::where('school_id', $schoolId)
                ->with('academicYear')
                ->orderBy('start_date', 'desc')
                ->get();

            $allInvoices = Invoice::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Filter invoices by selected term
        $invoices = $this->selectedTermId
            ? $allInvoices->where('term_id', $this->selectedTermId)
            : $allInvoices;

        // Term totals (always across ALL invoices for context)
        $totalBilledAll = (float) $allInvoices->sum('total_amount');
        $totalPaidAll = (float) $allInvoices->sum('paid_amount');
        $totalDueAll = (float) $allInvoices->sum('balance_amount');

        // Selected term totals
        $totalBilled = (float) $invoices->sum('total_amount');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $totalDue = (float) $invoices->sum('balance_amount');

        // School bank accounts
        $bankAccounts = $schoolId
            ? SchoolBankAccount::where('school_id', $schoolId)->where('is_active', true)->get()
            : collect();

        // Source bank list
        $bankList = config('banks', []);

        // Submissions (filtered to current term)
        $termInvoiceIds = $invoices->pluck('id')->toArray();
        $submissions = $student
            ? StudentPaymentSubmission::query()
                ->with('invoice')
                ->where('student_id', $student->id)
                ->when($this->selectedTermId, fn ($q) => $q->whereIn('invoice_id', $termInvoiceIds))
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        return [
            'student' => $student,
            'invoices' => $invoices,
            'allInvoices' => $allInvoices,
            'terms' => $terms,
            'selectedTermId' => $this->selectedTermId,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue,
            'totalBilledAll' => $totalBilledAll,
            'totalPaidAll' => $totalPaidAll,
            'totalDueAll' => $totalDueAll,
            'bankAccounts' => $bankAccounts,
            'bankList' => $bankList,
            'submissions' => $submissions,
        ];
    }
}
