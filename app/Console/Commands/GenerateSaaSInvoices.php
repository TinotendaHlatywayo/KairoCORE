<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\SaaS\Models\PlatformSetting;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\BillingService;

/**
 * Billing automation: raises the upcoming SaaS invoice for every billable
 * subscription (reached its first billing date, not cancelled, no open
 * invoice), controlled by Platform → Settings → SaaS Automation Rules.
 */
class GenerateSaaSInvoices extends Command
{
    protected $signature = 'saas:generate-invoices {--dry-run : Report what would be generated without creating invoices}';

    protected $description = 'Generates upcoming SaaS invoices for all billable subscriptions';

    public function handle(BillingService $billing): int
    {
        if (! $this->autoGenerateEnabled()) {
            $this->info('Automatic invoice generation is disabled in Platform Settings.');

            return self::SUCCESS;
        }

        // The service has no dry-run mode; report the eligible set instead.
        if ($this->option('dry-run')) {
            $eligible = SaaSSubscription::query()
                ->with('plan')
                ->whereNotIn('status', ['cancelled'])
                ->whereNotNull('billing_start_date')
                ->whereDate('billing_start_date', '<=', now()->startOfDay())
                ->whereDoesntHave('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'partially_paid']))
                ->count();

            $this->info("Would generate {$eligible} invoice(s).");

            return self::SUCCESS;
        }

        $summary = $billing->generateInvoicesForAllSchools();

        Log::info('SaaS invoice generation sweep', $summary);

        $this->info(sprintf(
            'Invoice sweep complete: %d processed, %d generated, %d skipped, %d failed.',
            $summary['processed'],
            $summary['generated'],
            $summary['skipped'],
            $summary['failed'],
        ));

        return self::SUCCESS;
    }

    protected function autoGenerateEnabled(): bool
    {
        return filter_var(
            PlatformSetting::get('automation', 'auto_generate_invoices', '1'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}
