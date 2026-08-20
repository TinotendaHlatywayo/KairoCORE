<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Models\SchoolSubscription;

class PlatformMetricsAggregator
{
    /**
     * Compile current KPIs for the dashboard cards.
     */
    public static function compileKPIs(): array
    {
        $totalSchools = School::count();
        $activeSchools = School::where('status', 'active')->count();
        $trialSchools = SchoolSubscription::where('status', 'trial')->count();

        // Sum transaction amounts safely using the exact 'amount' column
        $mrr = SaaSTransaction::where('status', 'paid')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return [
            'total_schools' => $totalSchools,
            'active_schools' => $activeSchools,
            'trial_schools' => $trialSchools,
            'mrr' => number_format($mrr, 2),
            'total_students' => DB::table('students')->count(),
            'total_users' => User::count(),
        ];
    }
}
