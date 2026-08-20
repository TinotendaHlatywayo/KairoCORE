<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\Login;
use App\Filament\Admin\Pages\PlatformBackupManager;
use App\Filament\Admin\Pages\PlatformIntelligenceDashboard;
use App\Filament\Admin\Pages\PlatformMaintenancePage;
use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Filament\Admin\Resources\PendingPaymentResource;
use App\Filament\Admin\Resources\PlatformAnnouncementResource;
use App\Filament\Admin\Resources\PlatformAuditLogResource;
use App\Filament\Admin\Resources\PlatformMessageResource;
use App\Filament\Admin\Resources\PlatformTemplateResource;
use App\Filament\Admin\Resources\SaaSInvoiceResource;
use App\Filament\Admin\Resources\SaaSPlanResource;
use App\Filament\Admin\Resources\SaaSTransactionResource;
use App\Filament\Admin\Resources\SchoolResource;
use App\Filament\Admin\Resources\SchoolSubscriptionResource;
use App\Filament\Admin\Resources\TenantHealthResource;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\PlatformAuthenticate;
use App\Http\Middleware\SetUserLocale;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
// Explicit platform management resources
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('platform')
            ->domain(parse_url(config('app.url'), PHP_URL_HOST))
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->pages([
                Pages\Dashboard::class,
                PlatformIntelligenceDashboard::class,
                PlatformBackupManager::class,
                PlatformMaintenancePage::class,
                PlatformSettingsPage::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Overview')->collapsible(false),
                NavigationGroup::make('Tenants')->collapsible(),
                NavigationGroup::make('Billing & Subscriptions')->collapsible(),
                NavigationGroup::make('Intelligence')->collapsible(),
                NavigationGroup::make('Operations')->collapsible(),
                NavigationGroup::make('Communication')->collapsible(),
                NavigationGroup::make('Platform Control')->collapsible(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetUserLocale::class,
            ])
            ->authMiddleware([
                PlatformAuthenticate::class,
                EnsurePlatformAdmin::class,
            ])
            ->resources([
                SchoolResource::class,
                TenantHealthResource::class,
                SaaSPlanResource::class,
                SchoolSubscriptionResource::class,
                SaaSInvoiceResource::class,
                PendingPaymentResource::class,
                SaaSTransactionResource::class,
                PlatformAnnouncementResource::class,
                PlatformTemplateResource::class,
                PlatformAuditLogResource::class,
                PlatformMessageResource::class,
            ]);
    }
}
