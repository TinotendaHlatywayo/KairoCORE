<?php

namespace Modules\SaaS\Services;

use App\Mail\SaaS\SaaSReceiptMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\SaaS\Models\SaaSAuditLog;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSSubscriptionHistory;
use Modules\SaaS\Models\SaaSTransaction;

class SubscriptionManager
{
    public function transitionSubscription(
        SaaSSubscription $subscription,
        int $newPlanId,
        string $billingPeriod,
        string $actionType,
        ?string $notes = null
    ): SaaSSubscription {
        return DB::transaction(function () use ($subscription, $newPlanId, $billingPeriod, $actionType, $notes) {
            $oldPlanId = $subscription->saas_plan_id;
            $newPlan = SaaSPlan::findOrFail($newPlanId);

            $subscription->update([
                'saas_plan_id' => $newPlanId,
                'billing_period' => $billingPeriod,
                'status' => 'active',
                'starts_at' => Carbon::now(),
                'ends_at' => match ($billingPeriod) {
                    'quarterly' => Carbon::now()->addMonths(3),
                    'yearly' => Carbon::now()->addYear(),
                    default => Carbon::now()->addMonth(),
                },
                'next_payment_date' => match ($billingPeriod) {
                    'quarterly' => Carbon::now()->addMonths(3)->toDateString(),
                    'yearly' => Carbon::now()->addYear()->toDateString(),
                    default => Carbon::now()->addMonth()->toDateString(),
                },
                'grace_ends_at' => Carbon::now()->addDays($newPlan->grace_days),
                'amount_due' => 0.00,
            ]);

            SaaSSubscriptionHistory::create([
                'school_id' => $subscription->school_id,
                'saas_subscription_id' => $subscription->id,
                'action_type' => $actionType,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlanId,
                'change_notes' => $notes ?? 'Executed subscription status alteration.',
                'performed_by_id' => Auth::id(),
            ]);

            SaaSAuditLog::create([
                'school_id' => $subscription->school_id,
                'performed_by_id' => Auth::id(),
                'event_type' => 'subscription_plan_modified',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'payload_before' => ['plan_id' => $oldPlanId],
                'payload_after' => ['plan_id' => $newPlanId, 'billing_period' => $billingPeriod],
            ]);

            return $subscription;
        });
    }

    public function processTransactionVerification(SaaSTransaction $transaction): SaaSReceipt
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'completed', 'processed_at' => Carbon::now()]);

            $invoice = $transaction->invoice;
            if ($invoice) {
                $invoice->update(['status' => 'paid', 'is_locked' => true]);

                $subscription = $invoice->subscription;
                if ($subscription) {
                    $nextDate = match ($subscription->billing_period) {
                        'quarterly' => Carbon::now()->addMonths(3)->toDateString(),
                        'yearly' => Carbon::now()->addYear()->toDateString(),
                        default => Carbon::now()->addMonth()->toDateString(),
                    };

                    $subscription->update([
                        'status' => 'active',
                        'last_payment_date' => Carbon::now()->toDateString(),
                        'next_payment_date' => $nextDate,
                        'ends_at' => Carbon::parse($nextDate),
                    ]);
                }
            }

            $latestReceipt = SaaSReceipt::orderBy('id', 'DESC')->first();
            $nextSequence = 1;
            if ($latestReceipt) {
                preg_match('/REC-SAAS-\d+-(\d+)/', $latestReceipt->receipt_number, $matches);
                if (isset($matches[1])) {
                    $nextSequence = ((int) $matches[1]) + 1;
                }
            }
            $receiptNumber = 'REC-SAAS-'.Carbon::now()->year.'-'.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);

            $receipt = SaaSReceipt::create([
                'school_id' => $transaction->school_id,
                'saas_invoice_id' => $transaction->saas_invoice_id,
                'saas_transaction_id' => $transaction->id,
                'receipt_number' => $receiptNumber,
                'amount_paid' => $transaction->amount,
                'currency' => $transaction->currency,
                'issued_at' => Carbon::now(),
            ]);

            // Automatically send the invoice/receipt notification email to the school administration email on file
            try {
                $recipientEmail = $transaction->school->email_address ?? 'admin@'.$transaction->school->subdomain.'.schoolcore.test';
                Mail::to($recipientEmail)->send(new SaaSReceiptMail($receipt));
            } catch (\Exception $e) {
                // Fail silently to prevent database transaction rollbacks due to local mail server disconnects
            }

            // Automatically register expense in school's finance ledger for SaaS subscription payment
            try {
                $schoolId = $transaction->school_id;
                $category = ExpenseCategory::firstOrCreate(
                    ['school_id' => $schoolId, 'name' => 'SaaS & Software Licensing'],
                    ['description' => __('Automated platform SaaS ERP subscription licensing expenses')]
                );

                $expenseType = ExpenseType::firstOrCreate(
                    ['school_id' => $schoolId, 'expense_category_id' => $category->id, 'name' => 'Platform Subscription']
                );

                Expense::create([
                    'school_id' => $schoolId,
                    'expense_type_id' => $expenseType->id,
                    'amount' => $transaction->amount,
                    'expense_date' => now()->toDateString(),
                    'reference_number' => 'EXP-SAAS-'.($invoice?->invoice_number ?? uniqid()),
                    'notes' => 'Automated expense record for SaaS subscription payment. Ref: '.$transaction->transaction_reference,
                    'status' => 'paid',
                ]);
            } catch (\Exception $e) {
                // Fail silently if finance tables aren't migrated or scoped in non-finance contexts
            }

            return $receipt;
        });
    }
}
