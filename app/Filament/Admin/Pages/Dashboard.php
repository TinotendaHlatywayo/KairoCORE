<?php

namespace App\Filament\Admin\Pages;

use App\Models\School;
use App\Support\PlatformTenantFilter;
use Filament\Pages\Dashboard as BaseDashboard;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSTransaction;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Command Center';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.admin.pages.dashboard';

    public function getViewData(): array
    {
        $schools = School::query();
        $transactions = SaaSTransaction::query();
        $manualSubmissions = SaaSManualSubmission::query();
        $invoices = SaaSInvoice::query();

        PlatformTenantFilter::excludeSchoolColumn($schools, 'id');
        PlatformTenantFilter::excludeSchoolColumn($transactions);
        PlatformTenantFilter::excludeSchoolColumn($manualSubmissions);
        PlatformTenantFilter::excludeSchoolColumn($invoices);

        return [
            'totalTenants' => (clone $schools)->count(),
            'activeTenants' => (clone $schools)->where('status', 'active')->count(),
            'pendingTenants' => (clone $schools)->where('status', 'pending')->count(),
            'totalRevenue' => (clone $transactions)->where('status', 'completed')->sum('amount'),
            'pendingConfirmations' => (clone $manualSubmissions)->where('status', 'pending')->count(),
            'unpaidInvoicesCount' => (clone $invoices)->where('status', 'unpaid')->count(),
            'recentTransactions' => (clone $transactions)->with('school')->latest('processed_at')->limit(5)->get(),
            'pendingSchools' => (clone $schools)->where('status', 'pending')->latest()->limit(5)->get(),
        ];
    }
}
