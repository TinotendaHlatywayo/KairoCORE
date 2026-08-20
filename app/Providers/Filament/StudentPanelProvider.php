<?php

namespace App\Providers\Filament;

use App\AvatarProviders\LocalSvgAvatarProvider;
use App\Filament\App\Pages\Auth\EditProfile;
use App\Filament\App\Pages\Auth\Login;
use App\Filament\Student\Pages\StudentDashboard;
use App\Http\Middleware\EnsureTenantNotSuspended;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SchoolPanelAuthenticate;
use App\Http\Middleware\SetUserLocale;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('student')

            // Local vector avatars (no ui-avatars.com dependency)
            ->defaultAvatarProvider(LocalSvgAvatarProvider::class)

            ->font('system-ui')

            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])

            // Reuse the school login page (self-registration defaults to a
            // "student" account). The page routes users to the correct panel.
            ->login(Login::class)
            ->passwordReset()

            ->profile(EditProfile::class)

            ->spa()

            ->brandLogo(fn () => view('modules.cms.brand-logo'))

            ->globalSearch(false)

            ->maxContentWidth(MaxWidth::Full)

            ->sidebarCollapsibleOnDesktop()

            ->assets([
                Css::make(
                    'filament-custom',
                    asset(Vite::asset('resources/css/filament-custom.css'))
                ),
                Css::make(
                    'panel-tailwind',
                    asset(Vite::asset('resources/css/panel-tailwind.css'))
                ),
            ])

            ->renderHook(
                'panels::head.end',
                fn () => view('modules.cms.dynamic-styles')
            )

            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('components.sidebar-toggle-btn')
            )

            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('components.back-button')
            )

            // 🚀 UNIFIED DATE & TIME + TASK MANAGER COMMAND CENTER
            // Mirrors the staff workspace topbar: a centered live date/time
            // trigger opening a Task Manager + interactive calendar dropdown.
            // Students can only manage their own tasks (no assign-to-others)
            // and only see student-targeted events.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => Blade::render('@livewire(\'student-topbar-command-center\')')
            )

            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('components.scroll-progress')
            )

            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('components.page-title-typing')
            )

            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('components.app-footer')
            )

            ->homeUrl(fn () => StudentDashboard::getUrl())

            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\\Filament\\Student\\Resources')

            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\\Filament\\Student\\Pages')

            ->navigationGroups([
                NavigationGroup::make(fn () => __('Learning'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Academics'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Fees & Payments'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Library'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Communication'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Boarding'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('My Account'))
                    ->collapsible(),
            ])

            ->discoverWidgets(in: app_path('Filament/Student/Widgets'), for: 'App\\Filament\\Student\\Widgets')

            ->middleware([
                ResolveTenant::class,
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
                SchoolPanelAuthenticate::class,
                EnsureUserActive::class,
            ])
            ->tenantMiddleware([
                EnsureTenantNotSuspended::class,
            ], isPersistent: true);
    }
}
