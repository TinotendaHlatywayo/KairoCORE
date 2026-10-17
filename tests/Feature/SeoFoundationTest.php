<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Technical SEO foundation: unique per-page metadata, canonical behaviour,
 * sitemap eligibility, robots directives (incl. AI crawlers), structured
 * data accuracy, and indexability of the public marketing surface.
 */
class SeoFoundationTest extends TestCase
{
    /** @return array<int, string> */
    private function marketingRoutes(): array
    {
        return [
            'home' => route('marketing.home'),
            'features' => route('marketing.features'),
            'school-fees' => route('marketing.school-fees'),
            'students' => route('marketing.students'),
            'admissions' => route('marketing.admissions'),
            'academics' => route('marketing.academics'),
            'security' => route('marketing.security'),
            'about' => route('marketing.about'),
            'contact' => route('marketing.contact'),
            'help' => route('marketing.help'),
            'help.enroll' => route('marketing.help.enroll'),
            'help.register' => route('marketing.help.register'),
            'help.fees' => route('marketing.help.fees'),
            'help.attendance' => route('marketing.help.attendance'),
            'help.reports' => route('marketing.help.reports'),
            'terms' => route('platform.terms'),
        ];
    }

    private function extractTitle(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/s', $html, $m);

        return trim(html_entity_decode($m[1] ?? '', ENT_QUOTES | ENT_HTML5));
    }

    public function test_every_public_page_has_a_unique_title_and_description(): void
    {
        $titles = [];
        $descriptions = [];

        foreach ($this->marketingRoutes() as $name => $url) {
            $response = $this->get($url);
            $response->assertOk();

            $html = $response->getContent();
            $title = $this->extractTitle($html);
            $titles[$name] = $title;

            preg_match('/<meta name="description" content="(.*?)"/s', $html, $m);
            $descriptions[$name] = trim(html_entity_decode($m[1] ?? '', ENT_QUOTES | ENT_HTML5));

            $this->assertNotSame('', $title, "Empty <title> on {$name}");
            $this->assertNotSame('', $descriptions[$name], "Empty meta description on {$name}");
            $this->assertStringNotContainsString('noindex', $html, "Public page {$name} carries noindex");
        }

        $this->assertSame(
            count($titles),
            count(array_unique($titles)),
            'Duplicate <title> tags: '.json_encode($titles)
        );
        $this->assertSame(
            count($descriptions),
            count(array_unique($descriptions)),
            'Duplicate meta descriptions: '.json_encode($descriptions)
        );
    }

    public function test_canonical_uses_configured_host_and_ignores_query_strings(): void
    {
        $home = $this->get(route('marketing.home'));
        $home->assertOk();
        preg_match('/<link rel="canonical" href="([^"]+)"/', $home->getContent(), $m);
        $this->assertNotEmpty($m[1] ?? null, 'Canonical tag missing on home');
        $this->assertStringStartsWith(config('app.url'), $m[1]);
        $this->assertStringNotContainsString('?', $m[1]);

        $filtered = $this->get('/features?category=finance');
        $filtered->assertOk();
        preg_match('/<link rel="canonical" href="([^"]+)"/', $filtered->getContent(), $f);
        $this->assertNotEmpty($f[1] ?? null, 'Canonical tag missing on filtered features');
        $this->assertSame(route('marketing.features'), $f[1]);
        $this->assertStringNotContainsString('category=', $f[1]);
    }

    public function test_home_and_features_carry_software_application_schema(): void
    {
        foreach (['home' => route('marketing.home'), 'features' => route('marketing.features')] as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('"@type":"SoftwareApplication"', $html, "Missing SoftwareApplication on {$name}");

            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
            $types = [];
            foreach ($matches[1] as $json) {
                $decoded = json_decode($json, true);
                $this->assertIsArray($decoded, "Invalid JSON-LD on {$name}");
                $types[] = $decoded['@type'] ?? '';
            }

            // Structured data must not be duplicated between layout and routes.
            $this->assertSame(1, count(array_keys($types, 'WebSite')), "Duplicate WebSite schema on {$name}");
            $this->assertSame(1, count(array_keys($types, 'Organization')), "Duplicate Organization schema on {$name}");
            $this->assertSame(1, count(array_keys($types, 'SoftwareApplication')), "Duplicate SoftwareApplication on {$name}");
        }
    }

    public function test_software_schema_contains_no_fabricated_offers_or_ratings(): void
    {
        $html = $this->get(route('marketing.home'))->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(\{[^<]*"@type":"SoftwareApplication"[^<]*\})</script>#s', $html, $m);
        $this->assertNotEmpty($m[1] ?? null, 'SoftwareApplication JSON-LD not found');

        $schema = json_decode($m[1], true);
        $this->assertIsArray($schema);
        $this->assertSame('EducationalApplication', $schema['applicationCategory'] ?? null);
        $this->assertSame('Web browser', $schema['operatingSystem'] ?? null);
        $this->assertStringStartsWith('https://', $schema['url'] ?? '');

        foreach (['offers', 'aggregateRating', 'review'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $schema, "Schema must not claim {$forbidden}");
        }
    }

    public function test_features_page_has_breadcrumb_and_single_instances_of_base_schema(): void
    {
        $html = $this->get(route('marketing.features'))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $types = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded, 'Invalid JSON-LD block on features');
            $types[] = $decoded['@type'] ?? '';
        }

        $this->assertSame(1, count(array_keys($types, 'WebSite')), 'Duplicate top-level WebSite schema');
        $this->assertSame(1, count(array_keys($types, 'Organization')), 'Duplicate top-level Organization schema');
        $this->assertSame(1, count(array_keys($types, 'BreadcrumbList')), 'Duplicate BreadcrumbList schema');
    }

    public function test_sitemap_contains_only_canonical_https_public_urls(): void
    {
        $xml = $this->get(route('marketing.sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml')
            ->getContent();

        preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);
        $urls = $m[1];
        $this->assertNotEmpty($urls);

        // URLs must be absolute and built from the configured canonical origin
        // (production runs APP_URL=https://kairocore.co.zw).
        $origin = rtrim(config('app.url'), '/');
        $privateFirstSegments = ['platform', 'workspace', 'student', 'register', 'activate-account', 'locale', 'language', '_cms-preview'];
        $seen = [];
        foreach ($urls as $loc) {
            $this->assertStringStartsWith($origin, $loc, "Sitemap URL not on canonical origin: {$loc}");

            $path = (string) parse_url($loc, PHP_URL_PATH);
            $firstSegment = trim(explode('/', $path)[1] ?? '', '/');
            $this->assertNotContains($firstSegment, $privateFirstSegments, "Private/noise URL in sitemap: {$loc}");

            $this->assertArrayNotHasKey($loc, $seen, "Duplicate sitemap entry: {$loc}");
            $seen[$loc] = true;
        }

        foreach ($this->marketingRoutes() as $routeName => $url) {
            $this->assertContains($url, $urls, "Public page missing from sitemap: {$url}");
        }
    }

    public function test_robots_allows_search_ai_crawlers_and_blocks_private_areas(): void
    {
        $body = $this->get(route('marketing.robots'))
            ->assertOk()
            ->getContent();

        foreach (['User-agent: OAI-SearchBot', 'Allow: /', 'Disallow: /platform', 'Disallow: /workspace', 'Disallow: /student', 'Disallow: /language/', 'Disallow: /locale/', 'Sitemap: '.url('/sitemap.xml')] as $needle) {
            $this->assertStringContainsString($needle, $body, "robots.txt missing: {$needle}");
        }
    }

    public function test_marketing_layout_serves_public_asset_pipeline_not_dev_cdn(): void
    {
        $html = $this->get(route('marketing.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html, 'Marketing pages must use the built Vite CSS, not the Tailwind CDN');
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.bunny.net"', $html);
    }
}
