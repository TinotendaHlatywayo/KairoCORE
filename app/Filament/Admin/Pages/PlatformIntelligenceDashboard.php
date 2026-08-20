<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Modules\Recovery\Services\TelemetryService;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSTransaction;

class PlatformIntelligenceDashboard extends Page
{
    protected static ?string $navigationGroup = 'Intelligence';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.platform-intelligence-dashboard';

    protected static ?int $navigationSort = 1;

    public array $telemetry = [];

    public float $totalRevenue = 0.00;

    public float $outstandingInvoicesTotal = 0.00;

    public $recentTransactions;

    public $outstandingInvoices;

    public $planBreakdown;

    public function mount(TelemetryService $service): void
    {
        if (session()->has('current_tenant')) {
            abort(403, 'Platform Administration Access Only.');
        }

        $this->telemetry = $service->gatherPlatformStats();

        // Comprehensive Financial Analytics
        $this->totalRevenue = (float) SaaSTransaction::where('status', 'completed')->sum('amount');
        $this->outstandingInvoicesTotal = (float) SaaSInvoice::where('status', 'unpaid')->sum('total');

        $this->recentTransactions = SaaSTransaction::with(['school', 'invoice'])
            ->latest('processed_at')
            ->limit(8)
            ->get();

        $this->outstandingInvoices = SaaSInvoice::with(['school', 'subscription.plan'])
            ->where('status', 'unpaid')
            ->latest('due_date')
            ->limit(8)
            ->get();

        $this->planBreakdown = SaaSPlan::withCount('subscriptions')
            ->get()
            ->map(function ($plan) {
                $subCount = $plan->subscriptions_count;
                $monthlyRev = $subCount * $plan->price_monthly;

                return [
                    'name' => $plan->name,
                    'subscribers' => $subCount,
                    'price' => $plan->price_monthly,
                    'mrr' => $monthlyRev,
                    'arr' => $monthlyRev * 12,
                ];
            });
    }
}
