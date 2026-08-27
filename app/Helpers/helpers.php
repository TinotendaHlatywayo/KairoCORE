<?php

use App\Models\School;
use App\Services\TerminologyService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Finance\Models\FinanceDocumentTemplate;

if (! function_exists('term')) {
    function term(string $key, string $default): string
    {
        return App::make(TerminologyService::class)->get($key, $default);
    }
}

if (! function_exists('honeypot_field_name')) {
    /**
     * A session-scoped, random honeypot field name. Chrome autofills fields
     * whose name/id match its heuristics (e.g. "company_website"), which made
     * legit submissions get silently discarded as spam. A random name generated
     * per session defeats both autofill and naive bots that target fixed names.
     */
    function honeypot_field_name(): string
    {
        $key = 'cms_honeypot_field_name';
        if (! session()->has($key)) {
            session([$key => 'f_'.Str::random(10)]);
        }

        return (string) session($key);
    }
}

if (! function_exists('platform_email_address')) {
    /**
     * The platform-level sending account. Tenant email configurations must
     * never reuse this identity as their sender.
     */
    function platform_email_address(): string
    {
        return (string) config('mail.platform.address', config('mail.from.address', 'hello@example.com'));
    }
}

if (! function_exists('platform_email_name')) {
    function platform_email_name(): string
    {
        return (string) config('mail.platform.name', config('mail.from.name', 'Kairo CORE'));
    }
}

if (! function_exists('platform_name')) {
    /**
     * The public SaaS platform name. Configurable under
     * Platform → Settings → SaaS Branding → "SaaS Platform Name".
     */
    function platform_name(): string
    {
        try {
            $name = \Modules\SaaS\Models\PlatformSetting::get('branding', 'platform_name');
        } catch (\Throwable $e) {
            $name = null;
        }

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : (string) config('app.name', 'Kairo CORE');
    }
}

if (! function_exists('platform_logo_url')) {
    /**
     * The platform logo uploaded under Platform → Settings → SaaS Branding.
     * Falls back to the bundled transparent logo.
     */
    function platform_logo_url(): string
    {
        return platform_branding_asset_url('platform_logo', 'images/logo-transparent.png');
    }
}

if (! function_exists('platform_favicon_url')) {
    /**
     * The platform favicon uploaded under Platform → Settings → SaaS Branding.
     * Falls back to the bundled favicon.
     */
    function platform_favicon_url(): string
    {
        return platform_branding_asset_url('platform_favicon', 'favicon.ico');
    }
}

if (! function_exists('platform_branding_asset_url')) {
    /**
     * Resolve an uploaded branding asset (logo/favicon) to a public URL.
     * FileUpload values are stored as JSON arrays of storage paths.
     */
    function platform_branding_asset_url(string $key, string $fallback): string
    {
        try {
            $value = \Modules\SaaS\Models\PlatformSetting::get('branding', $key);
        } catch (\Throwable $e) {
            $value = null;
        }

        $path = null;
        if (is_array($value)) {
            $path = $value[0] ?? null;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            $path = is_array($decoded) ? ($decoded[0] ?? null) : $value;
        }

        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return asset($fallback);
    }
}

if (! function_exists('school_favicon_url')) {
    /**
     * The active tenant's favicon: school-uploaded first, then the platform
     * favicon. Safe to call outside a tenant context.
     */
    function school_favicon_url(): string
    {
        try {
            if (app()->bound('current_tenant')) {
                $favicon = \Modules\Admin\Models\SystemSetting::get('branding', 'favicon_path');

                if (is_array($favicon)) {
                    $favicon = $favicon[0] ?? null;
                }

                if (is_string($favicon) && $favicon !== '' && Storage::disk('public')->exists($favicon)) {
                    return asset('storage/'.$favicon);
                }
            }
        } catch (\Throwable $e) {
            // fall through to platform favicon
        }

        return platform_favicon_url();
    }
}

if (! function_exists('is_platform_email')) {
    function is_platform_email(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return strtolower(trim($email)) === strtolower(trim(platform_email_address()));
    }
}

if (! function_exists('school_website_url')) {
    /**
     * Resolve the school's public website URL for printed documents.
     *
     * Prefers the custom website configured under System Settings →
     * Institution Profile, then any website_url set directly on the school
     * record, then the automatically assigned subdomain URL
     * (e.g. http://rujeko.lvh.me:8000/).
     */
    function school_website_url($school, ?string $configured = null): ?string
    {
        if (! empty($configured)) {
            return $configured;
        }

        if (! empty($school->website_url)) {
            return $school->website_url;
        }

        $subdomain = $school->subdomain ?? null;
        if (empty($subdomain)) {
            return null;
        }

        $parsed = parse_url((string) config('app.url'));
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'lvh.me';
        if (! empty($parsed['port'])) {
            $host .= ':'.$parsed['port'];
        }

        // ResolveTenant rewrites config('app.url') to the current request root
        // (e.g. https://tinwayacademy.lvh.me), so the host may already carry the
        // tenant subdomain. Detect that and return the URL as-is instead of
        // prepending the subdomain a second time.
        if ($subdomain && str_starts_with(strtolower($host), strtolower($subdomain).'.')) {
            return $scheme.'://'.$host.'/';
        }

        return $scheme.'://'.$subdomain.'.'.$host.'/';
    }
}

if (! function_exists('document_school_profile')) {
    /**
     * Resolve the school identity/contact details shown on printed finance
     * documents (invoice, receipt, statement).
     *
     * The Institution Profile tab in System Settings (system_settings group
     * "profile") is the source of truth. Falls back to the direct `schools`
     * table columns, then to sensible placeholders.
     */
    function document_school_profile($school, array $config = []): array
    {
        return [
            'name' => ($config['school_name'] ?? null) ?: ($school->name ?? 'School'),
            'motto' => $school->motto ?? 'Education for Excellence',
            'address' => ($config['address'] ?? null) ?: ($school->physical_address ?? 'Not Configured'),
            'phone' => ($config['phone'] ?? null) ?: ($school->phone_number ?? 'N/A'),
            'email' => ($config['email'] ?? null) ?: ($school->email_address ?? 'N/A'),
            'website' => school_website_url($school, $config['website_url'] ?? null) ?? 'N/A',
        ];
    }
}

if (! function_exists('finance_document_theme')) {
    /**
     * Resolve the active billing-document template for a school and return the
     * concrete CSS values used by the invoice/receipt/statement PDF blades.
     *
     * The "minimal_compact" preset renders strictly in black, white and grey,
     * so every semantic accent (success/danger/tints) collapses to grayscale.
     */
    function finance_document_theme($template, ?string $type, $school): array
    {
        if (! $template && $type && $school) {
            $template = FinanceDocumentTemplate::resolveFor((int) $school->id, $type);
        }

        $theme = $template ? $template->design_theme : 'classic_line';
        $layout = $template ? $template->resolveConfig() : FinanceDocumentTemplate::$themeDefaults['classic_line'];
        $sections = $template ? $template->resolveSections() : FinanceDocumentTemplate::sectionsFor('classic_line', []);

        $mono = (bool) (FinanceDocumentTemplate::$themeDefaults[$theme]['mono'] ?? ($theme === 'minimal_compact'));

        return [
            'theme' => $theme,
            'structure' => $layout['structure'] ?? 'classic',
            'header_color' => $layout['header_color'] ?? '#1e3a8a',
            'accent_color' => $layout['accent_color'] ?? '#1e3a8a',
            'table_header_bg' => $layout['table_header_bg'] ?? '#1e3a8a',
            'font_family' => $layout['font_family'] ?? 'Helvetica, sans-serif',
            'success_color' => $mono ? '#111827' : '#15803d',
            'danger_color' => $mono ? '#111827' : '#991b1b',
            'light_blue' => $mono ? '#111827' : '#1e40af',
            'light_green' => $mono ? '#111827' : '#166534',
            'light_red' => $mono ? '#111827' : '#991b1b',
            'blue_tint' => $mono ? '#f9fafb' : '#eff6ff',
            'green_tint' => $mono ? '#f9fafb' : '#f0fdf4',
            'red_tint' => $mono ? '#f9fafb' : '#fef2f2',
            'soft_border' => $mono ? '#e5e7eb' : '#bbf7d0',
            'sections' => $sections,
        ];
    }
}

if (! function_exists('finance_document_logo_path')) {
    /**
     * Resolve the logo shown on a finance document header.
     *
     * The template logo (header.logo) wins; when absent it falls back to the
     * school branding logo (billing config logo_path). The value may be a stored
     * relative path, an absolute filesystem path, or a web URL (used by the
     * live preview for just-uploaded temporary files).
     */
    function finance_document_logo_path(array $header, array $config): ?string
    {
        $logo = $header['logo'] ?? '';

        if (is_array($logo)) {
            $logo = array_values($logo)[0] ?? '';
        }

        $logo = is_string($logo) ? $logo : '';

        if ($logo !== '') {
            if (str_starts_with($logo, 'http')) {
                return $logo;
            }

            if (file_exists(public_path('storage/'.$logo))) {
                return public_path('storage/'.$logo);
            }
        }

        if (($config['show_logo'] ?? false) && ! empty($config['logo_path']) && file_exists(public_path('storage/'.$config['logo_path']))) {
            return public_path('storage/'.$config['logo_path']);
        }

        return null;
    }
}

if (! function_exists('resolve_public_asset_path')) {
    /**
     * Resolve a stored file path to a public-relative path (or null).
     * Handles both legacy "public/" paths and modern Laravel public-disk
     * paths (files stored under storage/app/public).
     */
    function resolve_public_asset_path(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $trimmed = ltrim($path, '/');

        if (file_exists(public_path($trimmed))) {
            return $trimmed;
        }

        if (file_exists(public_path('storage/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        if (file_exists(storage_path('app/public/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        return null;
    }
}

if (! function_exists('student_photo_src')) {
    /**
     * Absolute filesystem path of a student's photo for PDF embedding.
     * Falls back to the gender-appropriate default placeholder image
     * (no_profile_female.jpg for girls, no_profile_male.png for boys).
     */
    function student_photo_src($student): string
    {
        $resolved = resolve_public_asset_path($student->photo_path ?? null);
        if ($resolved) {
            return public_path($resolved);
        }

        $fallback = ($student->gender === 'female')
            ? 'images/no_profile_female.jpg'
            : 'images/no_profile_male.png';

        return public_path($fallback);
    }
}

if (! function_exists('current_tenant')) {
    /**
     * Resolve the current school from the tenant container binding, the session,
     * or the authenticated user, in that order.
     */
    function current_tenant(): ?School
    {
        if (app()->has('current_tenant') && app('current_tenant') instanceof School) {
            return app('current_tenant');
        }

        if (session()->has('current_tenant') && session('current_tenant') instanceof School) {
            return session('current_tenant');
        }

        $user = auth()->user();
        if ($user && $user->school_id) {
            return Cache::remember("tenant.school:{$user->school_id}", 300, function () use ($user) {
                return School::find($user->school_id);
            });
        }

        return null;
    }
}

if (! function_exists('resolve_app_locale')) {
    /**
     * Resolve the active locale from the session, the authenticated user, or
     * the current school, in that order. Falls back to the app fallback locale.
     * Safe to call outside HTTP contexts (queue jobs, mail) once the tenant has
     * been bound via current_tenant().
     *
     * Panel-scoped: the platform admin uses session('locale_admin') so tenant
     * language changes never leak into the platform interface.
     */
    function resolve_app_locale(): string
    {
        $school = current_tenant();
        $user = auth()->user();

        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
        $currentHost = request()->getHost();
        $isCentralDomain = $currentHost === $baseHost;

        $isPlatform = str_starts_with(request()->path(), 'platform');
        $sessionKey = $isPlatform ? 'locale_admin' : 'locale';

        // Priority 1: Explicit session choice (allows language switching)
        $locale = session($sessionKey);

        // Priority 2: If not central domain, check user & school locale
        if (! $locale && ! $isCentralDomain) {
            $locale = $user?->locale ?: ($school?->locale ?? null);
        }

        // Priority 3: Fallback (English for central domain if unset, otherwise app fallback)
        if (! $locale) {
            $locale = $isCentralDomain ? 'en' : config('app.fallback_locale', 'en');
        }

        $supported = ['en', 'sn', 'sw', 'fr', 'pt', 'es'];

        return in_array($locale, $supported, true) ? $locale : 'en';
    }
}

if (! function_exists('tenant_feature')) {
    /**
     * Step 1: Feature flags scoped per tenant.
     * Evaluates whether a feature is enabled for the current tenant context.
     * Falls back to base config/default if no tenant override exists.
     */
    function tenant_feature(string $featureKey, bool $default = false): bool
    {
        try {
            $tenant = current_tenant();
            if (! $tenant) {
                return (bool) config("features.{$featureKey}", $default);
            }

            $override = \Modules\Admin\Models\SystemSetting::get('features', $featureKey, null);
            if ($override !== null) {
                return filter_var($override, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (\Throwable $e) {
            // Fallback gracefully during unrun migrations or boot
        }

        return (bool) config("features.{$featureKey}", $default);
    }
}

if (! function_exists('tenant_config')) {
    /**
     * Step 2: Tenant configuration inheritance with override layers.
     * Merges base configuration with runtime tenant-specific overrides.
     */
    function tenant_config(string $key, mixed $default = null): mixed
    {
        $baseValue = config($key, $default);

        try {
            $tenant = current_tenant();
            if (! $tenant) {
                return $baseValue;
            }

            // Check if there is a tenant setting override for this key
            $parts = explode('.', $key);
            $group = $parts[0] ?? 'general';
            $settingKey = $parts[1] ?? $key;

            $override = \Modules\Admin\Models\SystemSetting::get($group, $settingKey, null);
            if ($override !== null) {
                return $override;
            }
        } catch (\Throwable $e) {
            // Fallback gracefully
        }

        return $baseValue;
    }
}

if (! function_exists('default_school_terms')) {
    function default_school_terms(): string
    {
        return '<h3>School Terms of Service & Student/Staff Conduct Agreement</h3>' .
               '<p>Welcome to our school portal. By registering an account and using this educational platform, you agree to abide by the following school-specific terms and policies:</p>' .
               '<ol>' .
               '<li><strong>Conduct & Academic Integrity:</strong> All students, staff, and parents agree to uphold the highest standards of academic honesty, respectful communication, and ethical behavior.</li>' .
               '<li><strong>Data Privacy & Acceptable Use:</strong> Users must not share login credentials, access unauthorized student records, or misuse school communication channels.</li>' .
               '<li><strong>Compliance with School Regulations:</strong> All activities on this platform are governed by school administration policies and applicable educational regulations.</li>' .
               '</ol>';
    }
}

if (! function_exists('email_branding')) {
    /**
     * Resolve the branding identity used by outgoing emails.
     *
     * Schools control their own automatically-sent emails (activation,
     * registration, admissions...) via Settings → Email Branding; the platform
     * controls its own (registration receipts to admins, SaaS billing) via
     * Platform Settings → Email Branding. Resolution order for school emails:
     * school email-branding settings → school profile fields → platform
     * email-branding settings → Kairo CORE defaults.
     *
     * @return array{logo_url:?string,company_name:string,company_address:?string,company_phone:?string,company_email:?string}
     */
    function email_branding(?\App\Models\School $school = null): array
    {
        $platform = function (string $key, mixed $default = null): mixed {
            try {
                return \Modules\SaaS\Models\PlatformSetting::get('email', $key, $default);
            } catch (\Throwable) {
                return $default;
            }
        };

        // Platform-level values first (they double as fallbacks).
        $logo = $platform('logo_path');
        $name = $platform('company_name') ?: config('app.name');
        $address = $platform('company_address');
        $phone = $platform('company_phone');
        $email = $platform('company_email');

        $normalizeLogo = function ($value): ?string {
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            if (blank($value)) {
                return null;
            }

            $relative = str_starts_with((string) $value, 'http') || str_starts_with((string) $value, 'storage/')
                ? str_replace('storage/', '', (string) $value)
                : $value;

            return file_exists(public_path('storage/'.$relative))
                ? asset('storage/'.$relative)
                : null;
        };

        $logoUrl = $normalizeLogo($logo);

        if ($school !== null) {
            $readSchoolSetting = function (string $key) use ($school): mixed {
                try {
                    $row = \Modules\Admin\Models\SystemSetting::query()
                        ->where('school_id', $school->id)
                        ->where('group', 'email')
                        ->where('key', $key)
                        ->value('value');

                    $decoded = is_string($row) ? json_decode($row, true) : $row;

                    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) === false && $decoded !== null
                        ? $decoded
                        : $row;
                } catch (\Throwable) {
                    return null;
                }
            };

            $logoUrl = $normalizeLogo($readSchoolSetting('logo_path'))
                ?? $normalizeLogo($school->logo_path)
                ?? $logoUrl;
            $name = $readSchoolSetting('company_name') ?: ($school->name ?: $name);
            $address = $readSchoolSetting('company_address') ?: ($school->physical_address ?: $address);
            $phone = $readSchoolSetting('company_phone') ?: ($school->phone_number ?: $phone);
            $email = $readSchoolSetting('company_email') ?: ($school->email_address ?: $email);
        }

        return [
            'logo_url' => $logoUrl ?? asset('images/logo-transparent.png'),
            'company_name' => $name ?: config('app.name'),
            'company_address' => filled($address) ? trim((string) $address) : null,
            'company_phone' => filled($phone) ? trim((string) $phone) : null,
            'company_email' => filled($email) ? strtolower(trim((string) $email)) : null,
        ];
    }
}

if (! function_exists('brand_email_view_data')) {
    /**
     * Merge resolved branding with per-email content into a ready-to-render
     * payload for resources/views/emails/brand.blade.php.
     */
    function brand_email_view_data(array $overrides = []): array
    {
        return array_merge([
            'logoUrl' => null,
            'companyName' => config('app.name'),
            'companyAddress' => null,
            'companyPhone' => null,
            'companyEmail' => null,
            'heading' => '',
            'greeting' => null,
            'introLines' => [],
            'actionUrl' => null,
            'actionText' => null,
            'outroLines' => [],
            'footerNote' => null,
            'signature' => null,
        ], $overrides);
    }
}

if (! function_exists('tenant_workspace_url')) {
    /**
     * Absolute URL to a workspace path on the SCHOOL's own subdomain.
     *
     * Never use Filament's getUrl() alone for cross-context links: it renders
     * against the current/central host, which sends a tenant user to the
     * central domain where their session cookie does not exist — bouncing them
     * into a login (or worse, the platform panel). This helper pins the host to
     * the school's own subdomain so tenants stay inside their space.
     */
    function tenant_workspace_url(?\App\Models\School $school, string $path = '/'): string
    {
        if ($school === null || blank($school->subdomain)) {
            return url($path);
        }

        return rtrim(school_website_url($school), '/').'/'.ltrim($path, '/');
    }
}
