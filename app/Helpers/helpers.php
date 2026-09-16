<?php

use App\Models\School;
use App\Services\TerminologyService;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Mode;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Models\SystemSetting;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Inventory\Models\InventoryItem;
use Modules\SaaS\Models\PlatformSetting;

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
            $name = PlatformSetting::get('branding', 'platform_name');
        } catch (Throwable $e) {
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
            $value = PlatformSetting::get('branding', $key);
        } catch (Throwable $e) {
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
                $favicon = SystemSetting::get('branding', 'favicon_path');

                if (is_array($favicon)) {
                    $favicon = $favicon[0] ?? null;
                }

                if (is_string($favicon) && $favicon !== '' && Storage::disk('public')->exists($favicon)) {
                    return asset('storage/'.$favicon);
                }
            }
        } catch (Throwable $e) {
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

        // If it's already a full URL or starts with storage/, return as-is
        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, 'storage/')) {
            return $trimmed;
        }

        // Check using Storage disk (most reliable for symlinks)
        if (Storage::disk('public')->exists($trimmed)) {
            return 'storage/'.$trimmed;
        }

        // Fallback: check common filesystem paths
        if (file_exists(public_path('storage/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        if (file_exists(storage_path('app/public/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        if (file_exists(public_path($trimmed))) {
            return $trimmed;
        }

        // Last resort: assume it's a valid public disk path and construct URL
        // This handles cases where file_exists fails due to symlink/permission issues
        // but the file actually exists. The browser will show 404 if truly missing.
        return 'storage/'.$trimmed;
    }
}

if (! function_exists('random_library_cover')) {
    /**
     * Return a random default library cover image URL.
     * Randomly picks from the 4 generated book cover design images.
     */
    function random_library_cover(): string
    {
        $covers = [
            'images/Book_cover_design_for_Kairo_202609010032.jpeg',
            'images/Book_cover_design_for_Kairo_202609010040.jpeg',
            'images/Book_cover_design_prompt_202609010035.jpeg',
            'images/Designing_default_school_book_cover_202609010037.jpeg',
        ];

        return asset($covers[array_rand($covers)]);
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

if (! function_exists('id_card_hex_to_rgba')) {
    /**
     * Convert any CSS color (hex / rgb / rgba / named) to an rgba() string
     * carrying the given alpha (0..1). Used to apply logo/card background
     * transparency to ID card elements in a DomPDF-safe way.
     */
    function id_card_hex_to_rgba(string $color, float $alpha = 1.0): string
    {
        $c = trim($color);
        $a = max(0, min(1, (float) $alpha));

        if (preg_match('/^#([a-fA-F0-9]{6})$/', $c, $m)) {
            $r = hexdec(substr($m[1], 0, 2));
            $g = hexdec(substr($m[1], 2, 2));
            $b = hexdec(substr($m[1], 4, 2));

            return "rgba({$r}, {$g}, {$b}, {$a})";
        }

        if (preg_match('/^#([a-fA-F0-9]{3})$/', $c, $m)) {
            $r = hexdec(str_repeat($m[1][0], 2));
            $g = hexdec(str_repeat($m[1][1], 2));
            $b = hexdec(str_repeat($m[1][2], 2));

            return "rgba({$r}, {$g}, {$b}, {$a})";
        }

        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $c, $m)) {
            return "rgba({$m[1]}, {$m[2]}, {$m[3]}, {$a})";
        }

        return $c;
    }
}

if (! function_exists('id_card_short_address')) {
    /**
     * Truncate a card address on a whole-word boundary so DomPDF never chops
     * mid-word into an ugly fragment like "Harare" -> "Hara..". Long addresses
     * are cut after the last whole word that still fits inside $max characters
     * and any truncation is marked with a single clean ellipsis.
     */
    function id_card_short_address(?string $address, int $max = 44): string
    {
        $text = trim((string) ($address ?: 'Borrowdale, Harare'));
        $width = max(16, (int) $max);

        if (mb_strlen($text) <= $width) {
            return $text;
        }

        $cut = mb_substr($text, 0, $width);

        if (preg_match('/\s/m', (string) $cut)) {
            $cut = mb_substr($cut, 0, mb_strrpos($cut, ' '));
        }

        $cut = rtrim($cut, " \t\n\r\0\x0B,.");

        return $cut.'…';
    }
}

if (! function_exists('id_card_gradient_data_uri')) {
    /**
     * Rasterise a 135° linear gradient between two colors into a base64 PNG
     * data URI. DomPDF cannot render CSS linear-gradient backgrounds, so the
     * gradient is pre-rendered with GD (when available) and cached on disk so
     * the browser preview, the printed PDF and the PNG export are identical.
     */
    function id_card_gradient_data_uri(string $start, string $end, int $width, int $height, float $opacity = 1.0): ?string
    {
        if (! function_exists('imagecreatetruecolor') || $width < 2 || $height < 2) {
            return null;
        }

        $width = max(2, (int) $width);
        $height = max(2, (int) $height);
        $opacity = max(0, min(1, (float) $opacity));
        $key = sha1(implode('|', [$start, $end, $width, $height, $opacity]));

        $dir = storage_path('app/public/id-card-bg-cache');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir."/{$key}.png";
        if (is_file($file)) {
            $data = (string) @file_get_contents($file);

            return $data !== '' ? 'data:image/png;base64,'.base64_encode($data) : null;
        }

        $img = @imagecreatetruecolor($width, $height);
        if ($img === false) {
            return null;
        }

        // Parse both stops into [r, g, b].
        $hex = function (string $color): array {
            $c = trim($color);
            if (preg_match('/^#([a-fA-F0-9]{6})$/', $c, $m)) {
                return [hexdec(substr($m[1], 0, 2)), hexdec(substr($m[1], 2, 2)), hexdec(substr($m[1], 4, 2))];
            }
            if (preg_match('/^#([a-fA-F0-9]{3})$/', $c, $m)) {
                return [hexdec(str_repeat($m[1][0], 2)), hexdec(str_repeat($m[1][1], 2)), hexdec(str_repeat($m[1][2], 2))];
            }
            if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $c, $m)) {
                return [(int) $m[1], (int) $m[2], (int) $m[3]];
            }

            return [255, 255, 255];
        };

        [$r1, $g1, $b1] = $hex($start);
        [$r2, $g2, $b2] = $hex($end);

        $lerp = function (float $t, int $a, int $b): int {
            return (int) round($a + ($b - $a) * $t);
        };

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // 135deg diagonal: normalized position across both axes.
                $t = (($x / max(1, $width - 1)) + ($y / max(1, $height - 1))) / 2;
                $t = max(0, min(1, $t));
                $col = imagecolorallocate($img, $lerp($t, $r1, $r2), $lerp($t, $g1, $g2), $lerp($t, $b1, $b2));
                imagesetpixel($img, $x, $y, $col);
            }
        }

        if ($opacity < 1.0) {
            $overlay = imagecolorallocatealpha($img, 255, 255, 255, (int) round((1 - $opacity) * 127));
            imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $overlay);
        }

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        @file_put_contents($file, $png);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}

if (! function_exists('id_card_file_data_uri')) {
    /**
     * Base64 data URI of a stored asset (public disk / public path / URL),
     * used to embed logos, photos and watermark images in DomPDF.
     */
    function id_card_file_data_uri(mixed $path): ?string
    {
        if (is_string($path)) {
            $trimmedPath = trim($path);
            if (str_starts_with($trimmedPath, '[') || str_starts_with($trimmedPath, '{')) {
                $decoded = json_decode($trimmedPath, true);
                if (is_array($decoded)) {
                    $path = reset($decoded) ?: null;
                }
            }
        }
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }
        if (empty($path) || ! is_string($path)) {
            return null;
        }

        $trimmed = ltrim($path, '/');

        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        $file = null;
        $cleanPath = str_replace('storage/', '', $trimmed);

        $candidates = [
            storage_path('app/public/'.$cleanPath),
            public_path('storage/'.$cleanPath),
            public_path($trimmed),
            storage_path('app/public/'.$trimmed),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }

        if (! $file || ! is_file($file)) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($file) : 'image/png';

        return 'data:'.($mime ?: 'image/png').';base64,'.base64_encode((string) @file_get_contents($file));
    }

    /**
     * Generate a QR code as a base64 data URI using the local chillerlan/php-qrcode library.
     *
     * The PNG is rendered with ECC level H (30% redundancy) and a standard 4-module quiet
     * zone. The pixel scale is derived from the QR's actual module count so the output is
     * generated as close as possible to the requested $size in pixels, producing a crisp,
     * high-resolution image that scans reliably at any display size.
     *
     * Falls back to the external API only if the library is unavailable.
     */
    function id_card_generate_qr(string $data, int $size = 600): string
    {
        try {
            if (class_exists(QRCode::class)) {
                $probe = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'eccLevel' => EccLevel::H,
                    'scale' => 1,
                    'addQuietzone' => true,
                    'quietzoneSize' => 4,
                ]);
                $probeCode = new QRCode($probe);
                foreach (Mode::INTERFACES as $interface) {
                    if ($interface::validateString($data)) {
                        $probeCode->addSegment(new $interface($data));
                        break;
                    }
                }
                $matrix = $probeCode->getQRMatrix();

                $moduleCount = max(1, $matrix->moduleCount);
                $scale = max(2, (int) ceil(max(1, (int) $size) / $moduleCount));

                $options = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'eccLevel' => EccLevel::H,
                    'scale' => $scale,
                    'addQuietzone' => true,
                    'quietzoneSize' => 4,
                    'outputBase64' => false,
                    'imageTransparent' => false,
                    'drawLightModules' => true,
                    'backgroundColor' => 'FFFFFF',
                    'gdImageUseUpscale' => false,
                ]);
                $pngData = (new QRCode($options))->render($data);

                return 'data:image/png;base64,'.base64_encode((string) $pngData);
            }
        } catch (Throwable $e) {
            // Fall through to external API
        }

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&ecc=H&format=png&data='.urlencode($data);
        $raw = @file_get_contents($url);

        return $raw ? 'data:image/png;base64,'.base64_encode($raw) : $url;
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

            $override = SystemSetting::get('features', $featureKey, null);
            if ($override !== null) {
                return filter_var($override, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (Throwable $e) {
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

            $override = SystemSetting::get($group, $settingKey, null);
            if ($override !== null) {
                return $override;
            }
        } catch (Throwable $e) {
            // Fallback gracefully
        }

        return $baseValue;
    }
}

if (! function_exists('default_school_terms')) {
    function default_school_terms(): string
    {
        return '<h3>School Terms of Service & Student/Staff Conduct Agreement</h3>'.
               '<p>Welcome to our school portal. By registering an account and using this educational platform, you agree to abide by the following school-specific terms and policies:</p>'.
               '<ol>'.
               '<li><strong>Conduct & Academic Integrity:</strong> All students, staff, and parents agree to uphold the highest standards of academic honesty, respectful communication, and ethical behavior.</li>'.
               '<li><strong>Data Privacy & Acceptable Use:</strong> Users must not share login credentials, access unauthorized student records, or misuse school communication channels.</li>'.
               '<li><strong>Compliance with School Regulations:</strong> All activities on this platform are governed by school administration policies and applicable educational regulations.</li>'.
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
    function email_branding(?School $school = null): array
    {
        $platform = function (string $key, mixed $default = null): mixed {
            try {
                return PlatformSetting::get('email', $key, $default);
            } catch (Throwable) {
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
                    $row = SystemSetting::query()
                        ->where('school_id', $school->id)
                        ->where('group', 'email')
                        ->where('key', $key)
                        ->value('value');

                    $decoded = is_string($row) ? json_decode($row, true) : $row;

                    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) === false && $decoded !== null
                        ? $decoded
                        : $row;
                } catch (Throwable) {
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
            'logo_url' => $logoUrl ?? platform_logo_url(),
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
    function tenant_workspace_url(?School $school, string $path = '/'): string
    {
        if ($school === null || blank($school->subdomain)) {
            return url($path);
        }

        return rtrim(school_website_url($school), '/').'/'.ltrim($path, '/');
    }
}

if (! function_exists('fuzzy_regex')) {
    /**
     * Build a MySQL REGEXP pattern that matches an ordered character
     * subsequence, so a typo like "crles" still finds "Charles".
     * Each typed character must appear in order somewhere in the value.
     */
    function fuzzy_regex(?string $term): string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return '.*';
        }

        $parts = preg_split('//u', preg_quote($term, '/'), -1, PREG_SPLIT_NO_EMPTY);

        return ($parts ? implode('.*', $parts) : '.*').'.*';
    }
}

if (! function_exists('fuzzy_search_where')) {
    /**
     * Apply an ordered character subsequence match against one or more columns.
     *
     * @param  Builder  $query
     */
    function fuzzy_search_where($query, array $columns, ?string $term): void
    {
        $pattern = fuzzy_regex($term);

        if ($pattern === '.*') {
            return;
        }

        $query->where(function ($q) use ($columns, $pattern) {
            foreach ($columns as $i => $column) {
                if ($i === 0) {
                    $q->whereRaw("{$column} REGEXP ?", [$pattern]);
                } else {
                    $q->orWhereRaw("{$column} REGEXP ?", [$pattern]);
                }
            }
        });
    }
}

if (! function_exists('inventory_item_search_options')) {
    /**
     * Searchable options for an inventory item picker: fuzzy match on name, sku
     * and item_type and render "Name — Type (Qty on hand)" labels so users can
     * see the item type and its current stock before choosing.
     *
     * @return array<int|string, string>
     */
    function inventory_item_search_options(?string $search, int $limit = 50): array
    {
        $query = InventoryItem::query()
            ->with('category');

        fuzzy_search_where($query, ['name', 'sku', 'item_type'], $search);

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->mapWithKeys(function ($item) {
                $label = $item->name;

                $metaBlock = '';
                if (is_array($item->meta_data) && count($item->meta_data)) {
                    $metaBlock = ' — '.collect($item->meta_data)
                        ->map(fn ($value, $key) => "{$key}: {$value}")
                        ->implode(' | ');
                }

                return [
                    $item->id => $label.' ('.ucfirst((string) $item->item_type).')'
                        .$metaBlock
                        ." — {$item->current_quantity} left",
                ];
            })
            ->all();
    }
}

if (! function_exists('inventory_item_label')) {
    /**
     * Display label for a single selected inventory item (used when a searchable
     * select shows the already-chosen record).
     */
    function inventory_item_label($item): ?string
    {
        if (! $item) {
            return null;
        }

        $label = $item->name.' ('.ucfirst((string) $item->item_type).')';

        if (is_array($item->meta_data) && count($item->meta_data)) {
            $metaBlock = collect($item->meta_data)
                ->map(fn ($value, $key) => "{$key}: {$value}")
                ->implode(' | ');
            $label .= ' — '.$metaBlock;
        }

        return $label;
    }
}
