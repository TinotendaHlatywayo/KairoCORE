<?php

namespace App\Providers\Filament;

use App\AvatarProviders\LocalSvgAvatarProvider;
use App\Filament\App\Pages\Academic\AcademicOperationsCenter;
use App\Filament\App\Pages\AdministrationDashboard;
use App\Filament\App\Pages\AdmissionSettingsPage;
use App\Filament\App\Pages\AnalyticsExplorer;
use App\Filament\App\Pages\ApplicationSuccess;
use App\Filament\App\Pages\Auth\EditProfile;
use App\Filament\App\Pages\Auth\Login;
use App\Filament\App\Pages\BillingDocumentSettingsPage;
use App\Filament\App\Pages\CommunicationCenter;
use App\Filament\App\Pages\EmailConfigurationPage;
use App\Filament\App\Pages\IssueBook;
use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\ReportGeneratorPage;
use App\Filament\App\Pages\ReportingDashboard;
use App\Filament\App\Pages\SaaSBillingOverview;
use App\Filament\App\Pages\Schedule;
use App\Filament\App\Pages\SystemSettingsPage;
use App\Filament\App\Pages\VisualCmsBuilder;
use App\Filament\App\Resources\AcademicYearResource;
use App\Filament\App\Resources\AssessmentWorkflowResource;
use App\Filament\App\Resources\ClassroomResource;
use App\Filament\App\Resources\ClinicVisitResource;
use App\Filament\App\Resources\CmsPageResource;
use App\Filament\App\Resources\CmsWebsiteResource;
use App\Filament\App\Resources\CourseResource;
use app\Filament\App\Resources\CustomRoleResource;
use app\Filament\App\Resources\DepartmentResource;
use App\Filament\App\Resources\EnterpriseReportTemplateResource;
use App\Filament\App\Resources\FixedAssetResource;
use App\Filament\App\Resources\GeneratedReportResource;
use App\Filament\App\Resources\GradingScaleResource;
use App\Filament\App\Resources\HomeworkResource;
use App\Filament\App\Resources\HostelAllocationResource;
use App\Filament\App\Resources\HostelAttendanceResource;
use App\Filament\App\Resources\HostelInspectionResource;
use App\Filament\App\Resources\HostelOutPassResource;
use App\Filament\App\Resources\HostelResource;
use App\Filament\App\Resources\HostelRoomResource;
use app\Filament\App\Resources\PlatformInboxResource;
use App\Filament\App\Resources\PromotionWorkflowResource;
use App\Filament\App\Resources\ReportTemplateResource;
use App\Filament\App\Resources\SaaSMySubscriptionResource;
use App\Filament\App\Resources\StockAdjustmentResource;
use App\Filament\App\Resources\StudentMedicalRecordResource;
use App\Filament\App\Resources\SubjectResource;
use app\Filament\App\Resources\SystemAuditLogResource;
use App\Filament\App\Resources\TeacherAssignmentResource;
use App\Filament\App\Resources\TimeSlotResource;
use App\Filament\App\Widgets\ReportingDashboardOverview;
use App\Http\Middleware\EnsureTenantNotSuspended;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SchoolPanelAuthenticate;
use App\Http\Middleware\SetUserLocale;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;
use Modules\Inventory\Filament\Resources\InventoryItemResource;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Filament\Resources\SupplierResource;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource;
use Modules\Library\Filament\Resources\EResourceResource;
use Modules\Library\Filament\Resources\LibraryBookResource;
use Modules\Library\Filament\Resources\LibraryIssueResource;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('workspace')

            // Register local vector avatar provider to disable ui-avatars.com API dependencies
            ->defaultAvatarProvider(LocalSvgAvatarProvider::class)

            // Register system local fonts to disable fonts.bunny.net CDN links
            ->font('system-ui')

            // Dynamic primary color loader resolved from tenant settings
            ->colors([
                'primary' => Color::Green,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->login(Login::class)
            ->passwordReset()

            // Account / profile page (name, email, change password) surfaced
            // through the user menu. The page itself uses the default panel
            // layout so it inherits the sidebar, topbar and tenant branding.
            ->profile(EditProfile::class)

            // Enables seamless, high-speed page transitions without browser-reload flashes
            ->spa()

            // Dynamic branding logo and name placement
            ->brandLogo(fn () => view('modules.cms.brand-logo'))

            // Remove the global search bar from the top header (next to the
            // notification bell); table-level search is still available.
            ->globalSearch(false)

            // UI/UX Layout Optimizations
            // Sidebar and topbar stay permanently visible (no auto-collapse
            // on scroll) so navigation is always within reach.
            ->maxContentWidth(MaxWidth::Full)

            // Collapsible sidebar: toggling collapses the rail to icons only
            // (hover expands it back to reveal labels — see filament-custom.css).
            ->sidebarCollapsibleOnDesktop()

            // Register the global custom CSS theme (cards, form fields,
            // buttons, nav items, accent indicators) on every page.
            ->assets([
                Css::make(
                    'filament-custom',
                    asset(Vite::asset('resources/css/filament-custom.css'))
                ),
                // Panel-wide Tailwind utilities so custom Blade views render
                // correctly (gradients, text colors, spacing) in light mode.
                Css::make(
                    'panel-tailwind',
                    asset(Vite::asset('resources/css/panel-tailwind.css'))
                ),
            ])

            // Inject custom styles and dynamic themes in head
            ->renderHook(
                'panels::head.end',
                fn () => view('modules.cms.dynamic-styles')
            )

            // Render sidebar toggle button at top header start
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('components.sidebar-toggle-btn')
            )

            // Always-visible "go to previous page" button (falls back to the
            // workspace dashboard when there is no browser history entry).
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('components.back-button')
            )

            // Render sidebar position switcher in top header bar end
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('components.sidebar-position-switcher')
            )

            // Render always-visible system settings shortcut in top header bar end
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('components.settings-shortcut')
            )

            // ──────────────────────────────────────────────────────────────
            // 🔍 MODERN SEARCH BAR – Using Blade View
            // ──────────────────────────────────────────────────────────────
            ->renderHook(
                'panels::sidebar.nav.start',
                fn () => view('components.sidebar-search')
            )

            // ──────────────────────────────────────────────────────────────
            // 🧭 MODULE WORKSPACE HEADER + CONTEXTUAL NAVIGATION
            // Renders a consistent module header and contextual tabs on every
            // tenant page. No-op on pages that do not belong to a module.
            // ──────────────────────────────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('components.module-navigation')
            )

            // Scroll progress bar at the very top of every page
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('components.scroll-progress')
            )

            // Typing effect for every page title (re-triggers on SPA nav)
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('components.page-title-typing')
            )

            // ──────────────────────────────────────────────────────────────
            // 🦶 STANDARD FOOTER – low-profile, "Powered by Tinway Technologies"
            // ──────────────────────────────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('components.app-footer')
            )

            // ──────────────────────────────────────────────────────────────
            // 🚀 UNIFIED DATE & TIME + TASK MANAGER COMMAND CENTER
            // Single centered trigger (live date/time) opening a split-pane
            // dropdown: Task Manager (left) + Interactive Calendar (right).
            // ──────────────────────────────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => Blade::render('@livewire(\'topbar-command-center\')')
            )

            // 1. Core Tenant Resource Discovery Path (Strictly School-Level Only)
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')

            // 2. Modular Discoveries
            ->discoverResources(in: base_path('Modules/Library/Filament/Resources'), for: 'Modules\\Library\\Filament\\Resources')
            ->discoverResources(in: base_path('Modules/Knowledge/Filament/Resources'), for: 'Modules\\Knowledge\\Filament\\Resources')

            // 3. Centralized Tenant-Level Resource Registration
            ->resources([
                SaaSMySubscriptionResource::class,

                // Academics & Grading
                GradingScaleResource::class,
                ReportTemplateResource::class,
                AcademicYearResource::class,
                CourseResource::class,
                SubjectResource::class,
                ClassroomResource::class,
                TimeSlotResource::class,
                TeacherAssignmentResource::class,
                AssessmentWorkflowResource::class,
                PromotionWorkflowResource::class,

                // Enterprise Reporting Module
                EnterpriseReportTemplateResource::class,
                GeneratedReportResource::class,

                // Library & eResources
                EResourceResource::class,
                LibraryBookResource::class,
                LibraryIssueResource::class,

                // Knowledge Repository
                KnowledgeGalleryResource::class,
                KnowledgeAssetResource::class,

                // Inventory & Fixed Assets
                FixedAssetResource::class,
                StockAdjustmentResource::class,
                InventoryItemResource::class,
                InventoryIssuanceResource::class,
                AssetMaintenanceResource::class,

                // Procurement
                SupplierResource::class,
                ProcurementRequestResource::class,
                PurchaseOrderResource::class,
                GoodsReceivedResource::class,

                // Boarding & Welfare
                HostelResource::class,
                HostelRoomResource::class,
                HostelAllocationResource::class,
                HostelOutPassResource::class,
                HostelAttendanceResource::class,
                HostelInspectionResource::class,

                // Health & Safety
                StudentMedicalRecordResource::class,
                ClinicVisitResource::class,

                // LMS
                HomeworkResource::class,

                // Visual Website & CMS Builder Resources
                CmsWebsiteResource::class,
                CmsPageResource::class,

                // Tenant-Level System Admin Resources
                CustomRoleResource::class,
                DepartmentResource::class,
                SystemAuditLogResource::class,

                // Cross-Tenant Platform Messaging (permission-gated)
                PlatformInboxResource::class,
            ])

            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Pages\Dashboard::class,
                SaaSBillingOverview::class,
                VisualCmsBuilder::class,

                // Custom Pages registration block
                ReportingDashboard::class,
                ReportGeneratorPage::class,
                AnalyticsExplorer::class,
                CommunicationCenter::class,

                // Academic Operations Center
                AcademicOperationsCenter::class,

                // Tenant-Level System Admin Pages
                AdministrationDashboard::class,
                SystemSettingsPage::class,
                EmailConfigurationPage::class,

                ApplicationSuccess::class,
                AdmissionSettingsPage::class,
                BillingDocumentSettingsPage::class,

                // Unified Schedule + My Day (Calendar ↔ Tasks)
                Schedule::class,
                MyDay::class,

                // Library
                IssueBook::class,
            ])

            // Navigation groups list.
            ->navigationGroups([
                NavigationGroup::make(fn () => __('Schedule & Tasks'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Admissions'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Students'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Academics'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Exams & Grading'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Finance'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('HR & Payroll'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Inventory & Procurement'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Library'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Boarding & Welfare'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Health & Safety'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Communication Center'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('LMS'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Knowledge Hub'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Website'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Reports & Intelligence'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('System Administration'))
                    ->collapsible(),

                NavigationGroup::make(fn () => __('Subscription & Billing'))
                    ->collapsible(),
            ])

            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                ReportingDashboardOverview::class,
            ])
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
