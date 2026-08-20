<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\School;
use Closure;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Modules\Admin\Models\SystemSetting;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Cache lifetime for the subdomain → school resolution. Keeps tenant
     * lookups (which run on every page AND every Livewire update) off the
     * database without ever going stale for long.
     */
    protected const TENANT_CACHE_TTL_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = config('app.url');
        $baseHost = parse_url($baseDomain, PHP_URL_HOST) ?? $baseDomain;

        if ($host === $baseHost) {
            return $next($request);
        }

        $school = $this->resolveSchool($host, $baseHost);

        if (! $school) {
            abort(404, 'School platform domain not registered.');
        }

        if ($school->status === 'suspended') {
            return response()->view('errors.suspended', ['school' => $school], 403);
        }

        if ($school->status === 'pending') {
            return response()->view('errors.pending', ['school' => $school], 403);
        }

        config(['app.url' => $request->root()]);
        URL::forceRootUrl($request->root());

        App::instance('current_tenant', $school);
        view()->share('school', $school);

        // Set default route parameter for {tenant} so route() helper works
        // without explicitly passing the tenant subdomain.
        URL::defaults(['tenant' => $school->subdomain]);

        // Dynamically override Filament's primary theme color based on current tenant setting [1.2]
        try {
            $theme = SystemSetting::get('branding', 'theme', 'emerald_heritage');

            $primaryColor = match ($theme) {
                'digital_cobalt' => Color::Blue,
                'obsidian_gold' => Color::Zinc,
                'crimson_academy' => Color::Red,
                'ocean_breeze' => Color::Teal,
                'forest_pine' => Color::Emerald,
                'sunset_amber' => Color::Orange,
                'royal_purple' => Color::Purple,
                'steel_slate' => Color::Slate,
                'rosewood' => Color::Rose,
                'dev_choice_1' => Color::Indigo,
                'dev_choice_2' => Color::Fuchsia,
                'dev_choice_3' => Color::Cyan,
                default => Color::Green, // emerald_heritage is Green
            };

            FilamentColor::register([
                'primary' => $primaryColor,
            ]);
        } catch (\Exception $e) {
            // Guard against unrun migrations or CLI installations
        }

        return $next($request);
    }

    /**
     * Resolve the active school for a host, caching the subdomain → school
     * mapping for a short window so page loads AND every Livewire update
     * avoid repeating the same lookup.
     */
    protected function resolveSchool(string $host, string $baseHost): ?School
    {
        if ($host !== $baseHost && str_ends_with($host, '.'.$baseHost)) {
            $subdomain = str_replace('.'.$baseHost, '', $host);

            return Cache::remember("tenant.subdomain:{$subdomain}", self::TENANT_CACHE_TTL_SECONDS, function () use ($subdomain) {
                return School::query()->where('subdomain', $subdomain)->first();
            });
        }

        return Cache::remember("tenant.domain:{$host}", self::TENANT_CACHE_TTL_SECONDS, function () use ($host) {
            $domainRecord = Domain::query()->where('domain', $host)->where('is_active', true)->first();

            return $domainRecord?->school;
        });
    }
}
