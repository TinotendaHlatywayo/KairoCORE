<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Services\ExchangeRateService;

class NotifyDefaulters extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'finance:notify-defaulters';

    /**
     * The console command description.
     */
    protected $description = 'Scans for overdue invoices and dispatches dual-currency parent alerts';

    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sweeping for outstanding school fees...');

        // 1. Retrieve all active invoices where balance is outstanding and the due date has passed
        $overdueInvoices = Invoice::with(['student'])
            ->where('balance_amount', '>', 0)
            ->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'void')
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('System clean. No outstanding fee balances found.');

            return;
        }

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            $student = $invoice->student;
            if (! $student) {
                continue;
            }

            $usdBalance = number_format($invoice->balance_amount, 2);

            // Convert primary USD balance to ZiG based on active school exchange rate
            $zigBalance = number_format($this->exchangeRateService->convertToZiG($invoice->balance_amount), 2);

            // Fetch guardian contact details or fall back to emergency values
            $parentName = 'Parent / Guardian';
            $parentEmail = null;
            $parentPhone = $student->emergency_contact_phone;

            // Optional: Resolves guardian relationship mapped in Phase 1
            if (method_exists($student, 'guardians') && $student->guardians()->exists()) {
                $guardian = $student->guardians()->first();
                $parentName = $guardian->name;
                $parentPhone = $guardian->phone;
                $parentEmail = $guardian->email;
            }

            $message = "Dear {$parentName}, our records indicate that your child {$student->first_name} {$student->last_name} has an outstanding school fee balance of \${$usdBalance} USD / {$zigBalance} ZiG. The due date was {$invoice->due_date->format('d-M-Y')}. Please resolve this balance to ensure uninterrupted classroom access.";

            // Log or dispatch simulated notification
            // In production, this targets an SMS gateway API (like Twilio, EcoCash, or Paynow) or dispatches mailers
            Log::info("Overdue Notice Sent: to {$parentPhone} / {$parentEmail} : {$message}");

            $this->line("Alert Issued: [{$student->admission_number}] {$student->first_name} - Balance: \${$usdBalance} USD / {$zigBalance} ZiG");
            $count++;
        }

        $this->info("Successfully issued {$count} outstanding fee balance alerts.");
    }
}
