<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\SaaS\Gateways\GatewayPayload;
use Modules\SaaS\Models\SaaSBillingSetting;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\BillingService;
use Modules\SaaS\Services\GatewayResolver;
use Modules\SaaS\Services\SubscriptionManager;

class SaaSBillingOverview extends Page
{
    use ModuleAwareActiveNavigation;
    use WithFileUploads;

    protected static string $view = 'filament.app.pages.saas-billing-overview';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Subscription & Billing';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Overview & Billing';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $slug = 'saas-billing-overview';

    public SaaSSubscription $subscription;

    public array $availablePlans = [];

    public SaaSBillingSetting $bankSettings;

    public string $checkoutCurrency = 'USD';

    public float $conversionRate = 26.7231; // Exchange rate used for ZiG conversions

    // Manual Payment Form properties
    public ?int $selectedInvoiceId = null;

    public string $refNumber = '';

    public float $payAmount = 0.00;

    public string $payDate = '';

    public string $bankName = '';

    public string $notes = '';

    public ?TemporaryUploadedFile $uploadedReceiptFile = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return Auth::check() && $user !== null && $user->school_id !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $schoolId = Auth::user()->school_id;

        $sub = SaaSSubscription::where('school_id', $schoolId)->first();
        if (! $sub) {
            $sub = SaaSSubscription::create([
                'school_id' => $schoolId,
                'saas_plan_id' => SaaSPlan::first()?->id ?? 1,
                'billing_period' => 'monthly',
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays(14),
                'next_payment_date' => now()->addDays(14)->toDateString(),
                'auto_renew' => false,
            ]);
        }
        $this->subscription = $sub;

        // Ensure there is at least one unpaid invoice so payment buttons are always visible
        $existingUnpaid = SaaSInvoice::where('school_id', $schoolId)->where('status', 'unpaid')->first();
        if (! $existingUnpaid) {
            try {
                app(BillingService::class)->generateUpcomingInvoice($sub);
            } catch (\Exception $e) {
                // Ignore if plan pricing is missing during initial scaffold
            }
        }

        $this->availablePlans = SaaSPlan::where('is_active', true)->get()->toArray();
        $this->bankSettings = SaaSBillingSetting::getActiveSettings();

        // Check for completed transactions when the user returns
        $this->pollPendingTransactions();
    }

    /**
     * Polls Paynow for the status of pending transactions to update system access instantly.
     */
    protected function pollPendingTransactions(): void
    {
        $schoolId = Auth::user()->school_id;
        $pendingTransactions = SaaSTransaction::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->where('payment_gateway_key', 'paynow')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return;
        }

        $resolver = app(GatewayResolver::class);
        $gateway = $resolver->resolve('paynow');

        foreach ($pendingTransactions as $tx) {
            // Poll Paynow's API using the transaction's unique pollUrl
            $response = $gateway->verifyPayment($tx->transaction_reference, []);

            if ($response->isSuccess) {
                app(SubscriptionManager::class)->processTransactionVerification($tx);

                Notification::make()
                    ->title(__('Payment Verified Successfully'))
                    ->body('Your transaction was processed successfully. System access has been restored.')
                    ->success()
                    ->send();
            }
        }
    }

    public function generateUpcomingBill(): void
    {
        $invoice = app(BillingService::class)->generateUpcomingInvoice($this->subscription);

        Notification::make()
            ->title(__('Invoice Generated'))
            ->body("Invoice Number: {$invoice->invoice_number} successfully created.")
            ->success()
            ->send();

        $this->redirect(route('filament.app.pages.saas-billing-overview'));
    }

    public function initializeOnlinePayment(int $invoiceId): void
    {
        $invoice = SaaSInvoice::findOrFail($invoiceId);
        $resolver = app(GatewayResolver::class);

        try {
            $gateway = $resolver->resolve('paynow');

            $chargeAmount = $invoice->total;
            $currency = 'USD';

            // Convert to ZiG if selected
            if ($this->checkoutCurrency === 'ZiG') {
                $chargeAmount = $invoice->total * $this->conversionRate;
                $currency = 'ZiG';
            }

            $payload = new GatewayPayload(
                amount: $chargeAmount,
                currency: $currency,
                invoiceNumber: $invoice->invoice_number,
                successUrl: route('filament.app.pages.saas-billing-overview'),
                cancelUrl: route('filament.app.pages.saas-billing-overview'),
                metaData: [
                    'email' => Auth::user()->email,
                    'result_url' => route('saas.paynow.webhook'),
                ]
            );

            $response = $gateway->initializePayment($payload);

            if ($response->isSuccess && $response->redirectUrl) {
                // Save the transaction's pollUrl
                SaaSTransaction::create([
                    'school_id' => $invoice->school_id,
                    'saas_invoice_id' => $invoice->id,
                    'payment_gateway_key' => 'paynow',
                    'transaction_reference' => $response->transactionReference, // Poll URL
                    'amount' => $invoice->total,
                    'currency' => $invoice->currency,
                    'status' => 'pending',
                ]);

                $this->redirect($response->redirectUrl);
            } else {
                throw new \Exception($response->errorMessage ?? 'Could not initialize Paynow session.');
            }
        } catch (\Exception $e) {
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
            'selectedInvoiceId' => 'required|exists:saas_invoices,id',
            'refNumber' => 'required|string|max:100',
            'payAmount' => 'required|numeric|min:0.01',
            'payDate' => 'required|date',
            'bankName' => 'required|string|max:255',
            'uploadedReceiptFile' => 'required|file|max:5120|mimes:jpeg,png,pdf',
        ]);

        $filePath = $this->uploadedReceiptFile->store('saas_receipts_proofs', 'public');

        SaaSManualSubmission::create([
            'school_id' => Auth::user()->school_id,
            'saas_invoice_id' => $this->selectedInvoiceId,
            'reference_number' => $this->refNumber,
            'amount' => $this->payAmount,
            'payment_date' => $this->payDate,
            'bank_name' => $this->bankName,
            'notes' => $this->notes,
            'receipt_file_path' => $filePath,
            'status' => 'pending',
        ]);

        Notification::make()
            ->title(__('Receipt Uploaded Successfully'))
            ->body('Our finance managers will review and approve your submission shortly.')
            ->success()
            ->send();

        $this->reset(['selectedInvoiceId', 'refNumber', 'payAmount', 'payDate', 'bankName', 'notes', 'uploadedReceiptFile']);
    }

    protected function getViewData(): array
    {
        $schoolId = Auth::user()->school_id;

        return [
            'invoices' => SaaSInvoice::where('school_id', $schoolId)->orderBy('id', 'DESC')->get(),
            'receipts' => SaaSReceipt::where('school_id', $schoolId)->orderBy('id', 'DESC')->get(),
            'history' => SaaSManualSubmission::where('school_id', $schoolId)->orderBy('id', 'DESC')->get(),
        ];
    }
}
