<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;

class CmsSeoService
{
    /**
     * Compile rich, structured JSON-LD schema metadata for search engines.
     *
     * Emits a schema.org "@graph" containing an EducationalOrganization (with
     * full NAP/contact details), a WebSite node, and - when a page is provided -
     * a WebPage node plus a BreadcrumbList.
     */
    public static function generateSchemaJson(object $school, CmsWebsite $website, ?CmsPage $page = null): string
    {
        $domain = request()->getSchemeAndHttpHost();
        $socials = collect((array) ($website->social_links ?? []))
            ->filter()
            ->values()
            ->map(fn ($url) => is_string($url) ? $url : (string) ($url['url'] ?? $url['value'] ?? ''))
            ->filter(fn ($url) => $url !== '')
            ->values()
            ->all();

        $logo = $school->logo_path ? asset('storage/'.$school->logo_path) : null;

        $organization = [
            '@type' => 'EducationalOrganization',
            '@id' => $domain.'/#organization',
            'name' => $school->name,
            'url' => $domain,
            'description' => trim((string) ($website->seo_global_description ?: $school->motto)),
            'telephone' => $school->phone_number ?: null,
            'email' => $school->email_address ?: null,
            'image' => $logo,
            'logo' => $logo,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $school->physical_address ?: 'Not Configured',
                'addressRegion' => $school->region ?: null,
                'addressCountry' => $school->country ?: null,
            ],
            'sameAs' => $socials ?: null,
        ];

        $graph = [$organization, [
            '@type' => 'WebSite',
            '@id' => $domain.'/#website',
            'url' => $domain,
            'name' => $school->name,
            'publisher' => ['@id' => $domain.'/#organization'],
        ]];

        if ($page) {
            $pageUrl = $page->is_homepage ? $domain : $domain.'/'.$page->slug;
            $pageTitle = $page->seo_title ?: ($page->is_homepage ? $school->name : $page->title.' | '.$school->name);

            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $pageUrl,
                'url' => $pageUrl,
                'name' => $pageTitle,
                'description' => $page->seo_description ?: null,
                'isPartOf' => ['@id' => $domain.'/#website'],
                'about' => ['@id' => $domain.'/#organization'],
                'inLanguage' => app()->getLocale() ?: 'en',
            ];

            if (! $page->is_homepage) {
                $breadcrumb = [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $domain],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $page->title, 'item' => $pageUrl],
                    ],
                ];
                $graph[] = $breadcrumb;
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        return '<script type="application/ld+json">'.json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>';
    }

    /**
     * Auto-generate a standardized, search-engine-compliant sitemap.xml.
     *
     * Excludes unpublished, noindex and "hide_from_sitemap" pages and honors
     * any explicit per-page canonical URL override.
     */
    public static function generateSitemapXml(int $schoolId): string
    {
        $pages = CmsPage::where('school_id', $schoolId)
            ->where('is_published', true)
            ->where('hide_from_sitemap', false)
            ->where('noindex', false)
            ->get();

        $domain = request()->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($pages as $page) {
            if ($page->canonical_url) {
                $loc = $page->canonical_url;
            } else {
                $loc = $domain.($page->is_homepage ? '' : '/'.$page->slug);
            }

            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($loc).'</loc>';
            $xml .= '<lastmod>'.$page->updated_at->toAtomString().'</lastmod>';
            $xml .= '<changefreq>'.($page->is_homepage ? 'daily' : 'weekly').'</changefreq>';
            $xml .= '<priority>'.($page->is_homepage ? '1.0' : '0.6').'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate a per-tenant robots.txt that references the dynamic sitemap.
     */
    public static function generateRobotsTxt(): string
    {
        $domain = request()->getSchemeAndHttpHost();

        return "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /_cms-preview/\n"
            ."Disallow: /cms-render/\n"
            ."Sitemap: {$domain}/sitemap.xml\n";
    }
}
