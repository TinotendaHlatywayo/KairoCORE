<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Modules\SaaS\Services\SubscriptionProvisioner;

return new class extends Migration
{
    /**
     * Give every existing institution a subscription row so the platform's
     * "School Subscriptions" screen lists every school and its plan. New
     * schools are provisioned by the admin create/approve flows from now on.
     */
    public function up(): void
    {
        $provisioner = app(SubscriptionProvisioner::class);

        School::query()
            ->doesntHave('saasSubscription')
            ->chunkById(100, function ($schools) use ($provisioner) {
                foreach ($schools as $school) {
                    try {
                        $provisioner->ensureForSchool($school);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
    }

    public function down(): void
    {
        // The provisioned subscriptions are indistinguishable from ones the
        // platform would create on first billing, so they are left in place.
    }
};
