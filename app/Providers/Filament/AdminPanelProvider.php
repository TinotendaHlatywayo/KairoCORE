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
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Platform branding is configurable at runtime under
        // Platform → Settings → SaaS Branding, so resolve it per request.
        $primary = null;
        try {
            $primary = \Modules\SaaS\Models\PlatformSetting::get('branding', 'default_primary');
        } catch (\Throwable $e) {
            $primary = null;
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('platform')
            ->domain(parse_url(config('app.url'), PHP_URL_HOST))
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            ->brandName(platform_name())
            ->brandLogo(fn () => view('filament.admin.partials.platform-brand-logo'))
            ->favicon(platform_favicon_url())
            ->colors([
                'primary' => is_string($primary) && preg_match('/^#[0-9a-fA-F]{6}$/', $primary) ? $primary : Color::Blue,
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
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => Blade::render('@livewire(\'admin-language-switcher\')')
            )
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
