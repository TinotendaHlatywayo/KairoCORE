<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Models\School;

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
        return [
            'totalTenants' => School::count(),
            'activeTenants' => School::where('status', 'active')->count(),
            'pendingTenants' => School::where('status', 'pending')->count(),
            'totalRevenue' => SaaSTransaction::where('status', 'completed')->sum('amount'),
            'pendingConfirmations' => SaaSManualSubmission::where('status', 'pending')->count(),
            'unpaidInvoicesCount' => SaaSInvoice::where('status', 'unpaid')->count(),
            'recentTransactions' => SaaSTransaction::with('school')->latest('processed_at')->limit(5)->get(),
            'pendingSchools' => School::where('status', 'pending')->latest()->limit(5)->get(),
        ];
    }
}
