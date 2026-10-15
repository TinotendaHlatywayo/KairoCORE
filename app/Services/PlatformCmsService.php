<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Collection;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsSiteTemplateService;

/**
 * The platform's own website CMS.
 *
 * KairoCORE's marketing site is managed with the exact same Content Manager the
 * schools use. That manager is tenant-driven, so we give the platform its own
 * reserved "site" (stored as a school row, never shown to tenants) and borrow
 * the tenant CMS services wholesale — no parallel CMS implementation.
 */
class PlatformCmsService
{
    /** Reserved subdomain that owns the platform website. Never a real tenant. */
    public const SUBDOMAIN = 'platform-site';

    /** The catalog template the platform website is seeded from. */
    public const TEMPLATE = 'modern-vibrant';

    public static function school(): School
    {
        return School::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                ['subdomain' => self::SUBDOMAIN],
                [
                    'name' => platform_name(),
                    'status' => 'active',
                    'settings' => ['platform_site' => true],
                ]
            );
    }

    public static function isPlatformSchool(?School $school): bool
    {
        return $school !== null && $school->subdomain === self::SUBDOMAIN;
    }

    /**
     * Run a callback with the platform site bound as the current tenant, then
     * restore whatever tenant (if any) was bound before.
     */
    public static function withContext(callable $callback)
    {
        $school = self::school();
        $hadTenant = app()->bound('current_tenant');
        $previous = $hadTenant ? app('current_tenant') : null;

        app()->instance('current_tenant', $school);

        // The tenant CMS services build public URLs with route('tenant.home'),
        // which needs the {tenant} default set (normally done by ResolveTenant).
        $url = app('url');
        $routeUrl = (fn () => $this->routeUrl())->call($url);
        $previousDefaults = $routeUrl->defaultParameters;
        $routeUrl->defaultParameters = array_merge($previousDefaults, ['tenant' => $school->subdomain]);

        try {
            return $callback($school);
        } finally {
            $routeUrl->defaultParameters = $previousDefaults;

            if ($hadTenant) {
                app()->instance('current_tenant', $previous);
            } else {
                app()->forgetInstance('current_tenant');
            }
        }
    }

    public static function liveWebsite(): ?CmsWebsite
    {
        return with(self::school(), fn (School $school) => CmsWebsite::withoutTenantScope()
            ->where('school_id', $school->id)
            ->where('is_template_site', false)
            ->orderBy('id')
            ->first());
    }

    /**
     * Resolve (creating on first use) the platform's live CMS website. The first
     * call seeds a full set of pages from the catalog template so the Content
     * Manager always opens onto something editable.
     */
    public static function ensureWebsite(): CmsWebsite
    {
        return self::withContext(function () {
            $live = CmsSiteTemplateService::liveWebsite();
            if ($live) {
                return $live;
            }

            $template = CmsSiteTemplateService::createFromCatalog(
                self::TEMPLATE,
                platform_name().' Website'
            );

            CmsSiteTemplateService::apply($template->id);

            return CmsSiteTemplateService::liveWebsite();
        });
    }

    public static function pageForSlug(string $slug): ?CmsPage
    {
        $website = self::liveWebsite();
        if (! $website) {
            return null;
        }

        if ($slug === '' || $slug === '/' || $slug === 'home') {
            return CmsPage::withoutTenantScope()
                ->where('cms_website_id', $website->id)
                ->where('is_homepage', true)
                ->first();
        }

        return CmsPage::withoutTenantScope()
            ->where('cms_website_id', $website->id)
            ->where('slug', ltrim($slug, '/'))
            ->first();
    }

    public static function navigationPages(): Collection
    {
        $website = self::liveWebsite();
        if (! $website) {
            return collect();
        }

        return CmsPage::withoutTenantScope()
            ->where('cms_website_id', $website->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();
    }
}
