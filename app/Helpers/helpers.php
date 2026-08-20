<?php

use App\Models\School;
use App\Services\TerminologyService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
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
        return (string) config('mail.platform.name', config('mail.from.name', 'SchoolCore'));
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
