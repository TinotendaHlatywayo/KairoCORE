<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Enforces the "Enable Automatic Trial Expiration Checks" rule from
 * Platform → Settings → SaaS Automation Rules: any active school whose free
 * trial window has elapsed is suspended (and its subscription, if still on a
 * trial, is suspended too). Paying tenants are never touched.
 */
class ExpireTrials extends Command
{
    protected $signature = 'saas:expire-trials {--dry-run : Report what would happen without suspending anything}';

    protected $description = 'Suspends schools whose free trial window has expired';

    public function handle(): int
    {
        if (! $this->autoExpireEnabled()) {
            $this->info('Automatic trial expiration is disabled in Platform Settings.');

            return self::SUCCESS;
        }

        $expired = School::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->where('status', 'active')
            ->get();

        $affected = 0;

        foreach ($expired as $school) {
            // A school that has already converted to a paid subscription is
            // out of the trial window and must never be suspended here.
            if ($school->saasSubscription?->status === 'active') {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Would suspend: {$school->name} (#{$school->id}), trial ended {$school->trial_ends_at}.");
                $affected++;

                continue;
            }

            DB::transaction(function () use ($school) {
                $subscription = $school->saasSubscription;

                if ($subscription && ! in_array($subscription->status, ['active', 'suspended'], true)) {
                    $subscription->update(['status' => 'suspended']);
                }

                $school->update(['status' => 'suspended']);
            });

            Log::warning('School suspended: free trial expired', [
                'school_id' => $school->id,
                'trial_ends_at' => $school->trial_ends_at?->toDateTimeString(),
            ]);

            $affected++;
        }

        $this->info(sprintf(
            'Trial expiration sweep complete: %d school(s) %s.',
            $affected,
            $this->option('dry-run') ? 'matched' : 'suspended',
        ));

        return self::SUCCESS;
    }

    protected function autoExpireEnabled(): bool
    {
        return filter_var(
            PlatformSetting::get('automation', 'auto_expire_trials', '1'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}
