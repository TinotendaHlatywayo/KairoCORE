<?php

namespace Modules\SaaS\Services;

use App\Mail\SaaS\PlatformBillingMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\PlatformBillingMessageTemplate;
use Modules\SaaS\Models\PlatformBillingNotificationLog;
use Modules\SaaS\Models\PlatformBillingSetting;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSSubscription;

/**
 * Drives the per-tenant billing lifecycle.
 *
 * Every tenant is billable from its own billing_start_date and repeats monthly
 * on billing_day_of_month. On each scheduler run this service works out, for
 * each tenant, which point of the cycle "today" falls on and sends the matching
 * message:
 *
 *   D-3  billing reminder with the amount
 *   D-1  day-before reminder with the amount
 *   D0   billing-day notice with the amount (+ super admin alert)
 *   D+1  overdue reminder
 *   D+4  notice that access will be suspended
 *   D+5  suspend access and email the reason + data-retention promise
 *
 * The offsets, retention window and recipient addresses are all editable by the
 * super admin (PlatformBillingSetting). A unique ledger row per
 * (school, template, billing date) guarantees each reminder fires at most once
 * even if the scheduler runs repeatedly.
 */
class BillingNotificationService
{
    /**
     * @return array{processed:int,sent:int,skipped:int,suspended:int}
     */
    public function run(?Carbon $onDate = null): array
    {
        $date = ($onDate ? $onDate->copy() : Carbon::today())->startOfDay();
        $settings = PlatformBillingSetting::current();

        $summary = ['processed' => 0, 'sent' => 0, 'skipped' => 0, 'suspended' => 0];

        $subscriptions = SaaSSubscription::query()
            ->with(['school', 'plan'])
            ->whereNotNull('billing_start_date')
            ->whereNotIn('status', ['cancelled'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $summary['processed']++;

            $result = $this->processSubscription($subscription, $date, $settings);

            $summary['sent'] += $result['sent'];
            $summary['skipped'] += $result['skipped'];
            $summary['suspended'] += $result['suspended'];
        }

        return $summary;
    }

    /**
     * @return array{sent:int,skipped:int,suspended:int}
     */
    protected function processSubscription(SaaSSubscription $subscription, Carbon $date, PlatformBillingSetting $settings): array
    {
        $result = ['sent' => 0, 'skipped' => 0, 'suspended' => 0];

        $school = $subscription->school;
        if (! $school) {
            return $result;
        }

        // Free period: no reminders, no suspensions until the first billing date.
        if ($date->lt($subscription->billing_start_date->copy()->startOfDay())) {
            return $result;
        }

        $billingDate = $this->resolveBillingDate($subscription, $date);
        $offset = $this->dayOffset($date, $billingDate);

        // Already paid for this cycle? next_payment_date only advances when a
        // payment is recorded, so anything beyond the billing date means the
        // tenant is ahead and should not be chased.
        if ($subscription->next_payment_date
            && Carbon::parse($subscription->next_payment_date)->startOfDay()->gt($billingDate)) {
            return $result;
        }

        $templateKeys = $this->applicableTemplateKeys($offset, $settings);
        if ($templateKeys === []) {
            return $result;
        }

        foreach ($templateKeys as $key) {
            $template = PlatformBillingMessageTemplate::query()
                ->where('key', $key)
                ->where('is_active', true)
                ->first();

            if (! $template) {
                $result['skipped']++;

                continue;
            }

            if ($this->alreadySent($school->id, $key, $billingDate)) {
                $result['skipped']++;

                continue;
            }

            $this->dispatch($template, $subscription, $billingDate, $offset, $settings);
            $result['sent']++;

            if ($key === 'billing_suspended') {
                $this->suspend($subscription);
                $result['suspended']++;
            }
        }

        return $result;
    }

    /**
     * The billing date the given day sits nearest to. Looking at the previous,
     * current and next month's occurrence of the billing day keeps the offset
     * correct whether today is just before (reminder) or just after (overdue).
     */
    public function resolveBillingDate(SaaSSubscription $subscription, Carbon $date): Carbon
    {
        $day = $subscription->billingDay();
        $monthStart = $date->copy()->startOfMonth();

        $candidates = [
            $monthStart->copy()->subMonthNoOverflow()->day($day),
            $monthStart->copy()->day($day),
            $monthStart->copy()->addMonthNoOverflow()->day($day),
        ];

        $best = null;
        foreach ($candidates as $candidate) {
            $candidate = $candidate->startOfDay();
            if ($best === null || abs($this->dayOffset($date, $candidate)) < abs($this->dayOffset($date, $best))) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Signed day distance: positive when $date is after the billing date
     * (overdue), negative when it is before (upcoming).
     */
    protected function dayOffset(Carbon $date, Carbon $billingDate): int
    {
        return (int) round(($date->startOfDay()->timestamp - $billingDate->startOfDay()->timestamp) / 86400);
    }

    /**
     * @return array<int, string>
     */
    protected function applicableTemplateKeys(int $offset, PlatformBillingSetting $settings): array
    {
        // Multiple templates may legitimately share the same offset (for
        // example when "days before" and "day before" are configured to the
        // same number). Accumulate into a list per offset instead of using the
        // offset as the array key, which would silently overwrite one template
        // with another.
        $schedule = [];
        $schedule[-1 * $settings->remind_days_before][] = 'billing_reminder';
        $schedule[-1 * $settings->day_before_reminder_offset][] = 'billing_day_before';
        $schedule[0][] = 'billing_due_today';
        $schedule[$settings->overdue_reminder_offset][] = 'billing_overdue';
        $schedule[$settings->suspension_warning_offset][] = 'billing_suspension_warning';
        $schedule[$settings->suspension_offset][] = 'billing_suspended';

        $keys = $schedule[$offset] ?? [];

        if ($offset === 0 && $settings->notify_super_admin_on_billing) {
            $keys[] = 'super_admin_billing_alert';
        }

        return $keys;
    }

    protected function alreadySent(int $schoolId, string $templateKey, Carbon $billingDate): bool
    {
        return PlatformBillingNotificationLog::query()
            ->where('school_id', $schoolId)
            ->where('template_key', $templateKey)
            ->whereDate('billing_date', $billingDate->toDateString())
            ->exists();
    }

    protected function dispatch(
        PlatformBillingMessageTemplate $template,
        SaaSSubscription $subscription,
        Carbon $billingDate,
        int $offset,
        PlatformBillingSetting $settings,
    ): void {
        $school = $subscription->school;
        $values = $this->placeholderValues($subscription, $billingDate, $offset, $settings);

        $subject = $template->renderSubject($values);
        $body = $template->renderBody($values);

        $isSuperAdminTemplate = str_starts_with($template->key, 'super_admin_billing_alert');
        $channel = match (true) {
            $template->send_platform_message && $template->send_email => 'both',
            $template->send_email => 'email',
            default => 'platform_message',
        };

        $emailSentAt = null;
        $emailError = null;
        $recipients = 0;

        if (! $isSuperAdminTemplate && $template->send_platform_message) {
            $recipients = $this->sendPlatformMessage($school->id, $subject, $body);
        }

        if ($template->send_email) {
            $recipient = $isSuperAdminTemplate
                ? ($settings->super_admin_billing_email ?: config('mail.platform.address'))
                : ($school->email_address ?: null);

            if ($recipient) {
                try {
                    Mail::to($recipient)->send(new PlatformBillingMail($subject, $body, $school->name));
                    $emailSentAt = now();
                    $recipients++;
                } catch (\Throwable $e) {
                    $emailError = $e->getMessage();
                    Log::warning('Billing notification email failed', [
                        'school_id' => $school->id,
                        'template' => $template->key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        PlatformBillingNotificationLog::create([
            'school_id' => $school->id,
            'saas_subscription_id' => $subscription->id,
            'template_key' => $template->key,
            'billing_date' => $billingDate->toDateString(),
            'channel' => $channel,
            'subject' => $subject,
            'recipient_count' => $recipients,
            'sent_at' => now(),
        ]);

        if ($emailError) {
            // Surface the failure without failing the ledger write: the message
            // id already guarantees we never resend, and the log captures why.
            PlatformBillingNotificationLog::query()
                ->where('school_id', $school->id)
                ->where('template_key', $template->key)
                ->whereDate('billing_date', $billingDate->toDateString())
                ->update(['sent_at' => $emailSentAt]);
        }
    }

    /**
     * Creates an in-app platform message for every user of the school and
     * raises the database notification.
     *
     * Delegates to {@see PlatformMessagingService} so automated billing notices
     * travel the exact same delivery/tracking path as manually-sent platform
     * messages (recipient rows, chunked notification fan-out, optional email).
     */
    protected function sendPlatformMessage(int $schoolId, string $subject, string $body): int
    {
        app(PlatformMessagingService::class)->sendFromPlatform(
            actor: null,
            subject: $subject,
            body: $body,
            priority: 'important',
            scope: 'single',
            schoolIds: [$schoolId],
            channel: 'platform_message',
        );

        return User::query()->where('school_id', $schoolId)->count();
    }

    protected function suspend(SaaSSubscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $subscription->update(['status' => 'suspended']);

            $school = $subscription->school;
            if ($school) {
                $school->update(['status' => 'suspended']);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function placeholderValues(
        SaaSSubscription $subscription,
        Carbon $billingDate,
        int $offset,
        PlatformBillingSetting $settings,
    ): array {
        $school = $subscription->school;
        $plan = $subscription->plan;

        $invoice = SaaSInvoice::query()
            ->where('saas_subscription_id', $subscription->id)
            ->where('status', '!=', 'paid')
            ->orderByDesc('id')
            ->first()
            ?? SaaSInvoice::query()
                ->where('saas_subscription_id', $subscription->id)
                ->orderByDesc('id')
                ->first();

        return [
            'school_name' => (string) $school->name,
            'amount' => number_format($subscription->getBillingAmount(), 2),
            'currency' => (string) ($plan->currency ?? 'USD'),
            'billing_date' => $billingDate->format('d M Y'),
            'due_date' => $billingDate->format('d M Y'),
            'plan_name' => (string) ($plan->name ?? ''),
            'days' => (string) abs($offset),
            'retention_months' => (string) $settings->data_retention_months,
            'invoice_number' => (string) ($invoice->invoice_number ?? '—'),
            'subdomain' => (string) $school->subdomain,
            'url' => tenant_workspace_url($school, 'workspace/saas-billing-overview'),
            'status' => (string) $subscription->status,
            'contact_email' => (string) ($school->email_address ?? ''),
            'registration_date' => optional($school->created_at)->format('d M Y') ?? '',
        ];
    }
}
