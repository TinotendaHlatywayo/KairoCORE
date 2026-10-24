<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\ResolveTenant;
use App\Livewire\Assessment\MarkingQueue;
use App\Livewire\Assessment\TakeAssessment;
use App\Livewire\Exams\PortalReportsPublisher;
use App\Livewire\TopbarCommandCenter;
use App\Models\School;
use App\Models\User;
use App\Navigation\ModuleAwareNavigationManager;
use App\Navigation\ModuleNavigationService;
use App\Policies\CmsPagePolicy;
use App\Policies\CmsWebsitePolicy;
use App\Policies\SchoolPolicy;
use App\Services\ImageConverter;
use App\Support\PdfFonts;
use App\Support\UploadReason;
use Barryvdh\DomPDF\PDF;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Navigation\NavigationManager;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Modules\Admin\Models\SystemAuditLog;
use Modules\Admin\Services\AuditLogger;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Modules\Communication\Livewire\ChatWorkspace;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\DigitalAssessment\Policies\DigitalAssessmentPolicy;
use Modules\DigitalAssessment\Policies\QuestionBankPolicy;
use Modules\HR\Models\Employee;
use Modules\Reports\Services\DatasetRegistry;
use Modules\SaaS\Models\PlatformAuditLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantEmailConfigurationService::class);

        // Resolve module navigation once per request. The service memoizes
        // resolved tabs (each tab boots a Filament Resource/Page class to
        // obtain its URL), so sharing a single instance across the sidebar
        // filter, the module header and visibility checks avoids re-booting
        // ~100 classes on every render.
        $this->app->singleton(ModuleNavigationService::class);

        // The report dataset registry scans the provider directory and rebuilds
        // every dataset definition on first use, then memoizes it. Resolving a
        // fresh instance per injection point repeated that scan for every
        // service that touches report data, so it is shared per request.
        $this->app->singleton(DatasetRegistry::class);

        // Replace Filament's default navigation manager so the sidebar hides
        // every module the tenant disabled in System Settings -> Manage Modules.
        // Bound after Filament's own scoped binding (this provider boots later)
        // and resolved lazily per request, so the super-admin panel keeps the
        // stock behaviour (no tenant => all modules visible).
        $this->app->scoped(
            NavigationManager::class,
            fn (): NavigationManager => new ModuleAwareNavigationManager,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 0. Surface lazy-loading and silent-discard bugs early during local
        // development so N+1 query regressions are caught before they ship.
        // Deliberately scoped to local so production stays lean.
        if ($this->app->environment('local')) {
            Model::shouldBeStrict();

            // A missed eager-load must never take down a whole page: log the
            // violation as an N+1 warning instead of throwing. The other
            // strict guards (accessing missing attributes, silently discarding
            // non-fillable input) remain fatal because they indicate real
            // data-integrity bugs.
            Model::handleLazyLoadingViolationUsing(function ($model, string $key): void {
                report(new LazyLoadingViolationException($model, $key));
            });
        }

        // 0. Register the server's TrueType fonts with DomPDF so the finance
        //    document "Document Font" choices render correctly in printed PDFs.
        //    Registration is idempotent (metrics are cached in storage/fonts).
        if (class_exists(PDF::class)) {
            try {
                PdfFonts::register(app('dompdf'));
            } catch (\Throwable $e) {
                // Fonts are a progressive enhancement; never block the app on them.
            }
        }

        // 0. Explain failed uploads with the reason and the real limit
        //    ("larger than this site's 64 MB limit — try something smaller")
        //    instead of the framework's bare "failed to upload".
        UploadReason::register();

        // 0. Register application-wide rate limiters
        RateLimiter::for('rate_limit:password_reset', function ($request) {
            $email = strtolower($request->input('email', ''));
            $ip = $request->ip();

            return [
                Limit::perHour(3)->by($email),
                Limit::perHour(5)->by($ip),
            ];
        });

        RateLimiter::for('rate_limit:high_cost_actions', function ($request) {
            $identifier = $request->input('email') ?: ($request->input('phone') ?: $request->ip());

            return Limit::perMinutes(2, 2)->by($identifier);
        });

        RateLimiter::for('rate_limit:registration', function ($request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('rate_limit:exports', function ($request) {
            $userId = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(5)->by($userId);
        });

        RateLimiter::for('rate_limit:search', function ($request) {
            $userId = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(30)->by($userId);
        });

        RateLimiter::for('rate_limit:api', function ($request) {
            $userId = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(80)->by($userId);
        });

        // 1. Force all generated URLs to HTTPS whenever the app is configured
        //    to be served over TLS (APP_URL=https://...). Keeps every redirect,
        //    notification link and asset URL on the secure scheme.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // 1. Register the modular database migration paths
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(base_path('Modules/Library/Database/Migrations'));
            $this->loadMigrationsFrom(base_path('Modules/Knowledge/Database/Migrations'));
            $this->loadMigrationsFrom(base_path('Modules/Inventory/Database/Migrations'));
            $this->loadMigrationsFrom(base_path('Modules/DigitalAssessment/Database/Migrations'));
        }

        // 2. Force Livewire's internal update endpoint to run through the Tenant Resolver
        // This ensures the active subdomain session is parsed and verified securely.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    ResolveTenant::class,   // tenant resolved FIRST
                    'web',                  // then session, CSRF, etc.
                ]);
        });

        // Register the unified topbar command center (date/time + task manager)
        Livewire::component('topbar-command-center', TopbarCommandCenter::class);

        // Globally disable row-click record navigation across all Filament tables so clicking a record does nothing
        if (class_exists(Table::class)) {
            Table::configureUsing(function (Table $table): void {
                $table->recordUrl(null);
            });
        }

        // Convert freshly uploaded images to WebP server-side (PHP GD) before they are persisted.
        // Wrapping Filament's storage callback (important => registered after setUp's default) means
        // only files uploaded in this request are touched; stored paths already in state pass through
        // unchanged, and any conversion failure keeps the original file.
        BaseFileUpload::configureUsing(function (BaseFileUpload $component): void {
            $property = new \ReflectionProperty(BaseFileUpload::class, 'saveUploadedFileUsing');
            $original = $property->getValue($component);

            if (! $original instanceof \Closure) {
                return;
            }

            $component->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file) use ($original): ?string {
                $stored = $component->evaluate($original, ['file' => $file]);

                if (! is_string($stored) || $stored === '') {
                    return $stored;
                }

                return ImageConverter::convert($stored, $component->getDiskName());
            });
        }, isImportant: true);

        // Restrict system access for suspended or terminated staff
        Event::listen(Authenticated::class, function ($event) {
            $user = $event->user;
            if ($user) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee && in_array($employee->status, ['suspended', 'terminated'], true)) {
                    Auth::logout();
                    if (request()->hasSession()) {
                        request()->session()->invalidate();
                        request()->session()->regenerateToken();
                    }
                    abort(403, __('Your staff account has been suspended or terminated. You cannot access the system until cleared by an administrator.'));
                }
            }
        });

        // ChatWorkspace lives in the Modules namespace, outside Livewire's
        // default class_namespace (App\Livewire). Without an explicit alias,
        // Livewire can resolve it when first rendered (by class) but cannot
        // reverse-map its name back to a class on subsequent update requests,
        // which throws LivewireReleaseTokenMismatchException and 419s the send.
        Livewire::component('communication.chat-workspace', ChatWorkspace::class);
        Livewire::component('assessment.take-assessment', TakeAssessment::class);
        Livewire::component('assessment.marking-queue', MarkingQueue::class);
        Livewire::component('exams.portal-reports-publisher', PortalReportsPublisher::class);

        // Same failure mode, generalized: Filament renders page header/footer
        // widgets by CLASS (@livewire($widgetClass)), so the first render
        // works — but update requests only carry the derived NAME (e.g.
        // "app.filament.admin.widgets.platform-institution-overview-widget").
        // With the default class_namespace (App\Livewire) that name cannot be
        // reverse-mapped, so ReleaseToken::verify() throws
        // LivewireReleaseTokenMismatchException and the update 419s. That 419
        // is never logged and survives every refresh/cookie-clear, because the
        // mapping is structurally missing. Map any otherwise-unresolvable
        // "app.*" name back onto its App\* component class.
        Livewire::resolveMissingComponent(function (string $name): ?string {
            if (! str_starts_with($name, 'app.')) {
                return null;
            }

            // The leading "app" segment IS the App\ root namespace — drop it
            // instead of prepending a second one.
            $class = '\\App\\'.collect(explode('.', $name))
                ->skip(1)
                ->map(fn (string $segment): string => (string) str($segment)->studly())
                ->join('\\');

            if (! class_exists($class) || ! is_subclass_of($class, Component::class)) {
                return null;
            }

            return $class;
        });

        // 3. Register the strict platform policy mapping
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(CmsWebsite::class, CmsWebsitePolicy::class);
        Gate::policy(QuestionBank::class, QuestionBankPolicy::class);
        Gate::policy(DigitalAssessment::class, DigitalAssessmentPolicy::class);

        // 4. Global Autonomic Auditing Engine: Intercepts all database changes globally
        Event::listen('eloquent.*', function ($event, array $models) {
            $model = $models[0] ?? null;
            if (! $model instanceof Model) {
                return;
            }

            // Safeguard: Exclude audit log model itself to prevent infinite recursive loop crashes
            if ($model instanceof SystemAuditLog) {
                return;
            }

            // The platform's own hand-off trail (Enter/Leave school) lives
            // outside the tenant's audit scope by design.
            if ($model instanceof PlatformAuditLog) {
                return;
            }

            // Shadow accounts created for platform impersonation are machinery,
            // never tenant activity: their row writes (create/refresh during
            // entry) must not appear in the school's audit log or user lists.
            if ($model instanceof User && $model->is_platform_managed) {
                return;
            }

            // Exclude transient and platform-specific sessions
            if ($model instanceof DatabaseSessionHandler) {
                return;
            }

            // Extract action type and record modifications
            if (str_contains($event, 'eloquent.created:')) {
                AuditLogger::log(
                    'Created Record inside: '.class_basename($model),
                    $this->resolveModuleFromNamespace(get_class($model)),
                    null,
                    $model->toArray()
                );
            } elseif (str_contains($event, 'eloquent.updated:')) {
                $changes = $model->getChanges();
                if (empty($changes)) {
                    return;
                }

                // Keep logs clean by intersecting only changed attributes
                $original = array_intersect_key($model->getOriginal(), $changes);

                AuditLogger::log(
                    'Updated Record inside: '.class_basename($model),
                    $this->resolveModuleFromNamespace(get_class($model)),
                    $original,
                    $changes
                );
            } elseif (str_contains($event, 'eloquent.deleted:')) {
                AuditLogger::log(
                    'Deleted Record inside: '.class_basename($model),
                    $this->resolveModuleFromNamespace(get_class($model)),
                    $model->toArray(),
                    null
                );
            }
        });
    }

    /**
     * Map namespaces to descriptive human-readable SaaS module tags.
     */
    private function resolveModuleFromNamespace(string $className): string
    {
        $parts = explode('\\', $className);
        if ($parts[0] === 'Modules' && isset($parts[1])) {
            return match (strtolower($parts[1])) {
                'hr' => 'HR & Payroll',
                'finance' => 'Finance & Cohorts',
                'library' => 'Library Desk',
                'inventory' => 'Inventory & Assets',
                'clinic' => 'Clinic & Health',
                'hostels' => 'Boarding & Welfare',
                'cms' => 'Website Builder',
                'communication' => 'Communication Center',
                'academics' => 'Academics & SIS',
                default => ucwords($parts[1])
            };
        }

        return 'System Administration';
    }
}
