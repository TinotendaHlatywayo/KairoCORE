<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Public platform SEO surface: canonical URLs, social meta, structured data,
 * the central robots.txt / sitemap.xml pair, and noindex on private pages.
 */
class PlatformSeoTest extends TestCase
{
    public function test_marketing_home_exposes_canonical_social_and_structured_data(): void
    {
        $response = $this->get(route('marketing.home'));

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('name="twitter:card"', false);
    }

    public function test_about_page_includes_breadcrumb_structured_data(): void
    {
        $response = $this->get(route('marketing.about'));

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_contact_page_includes_breadcrumb_structured_data(): void
    {
        $response = $this->get(route('marketing.contact'));

        $response->assertOk();
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_sitemap_lists_public_marketing_pages(): void
    {
        $response = $this->get(route('marketing.sitemap'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee(route('marketing.home'), false);
        $response->assertSee(route('marketing.about'), false);
        $response->assertSee(route('marketing.contact'), false);
    }

    public function test_public_school_directory_is_not_available(): void
    {
        $this->assertFalse(Route::has('marketing.schools'));

        $this->get('/schools')->assertNotFound();
    }

    public function test_robots_references_sitemap_and_blocks_private_areas(): void
    {
        $response = $this->get(route('marketing.robots'));

        $response->assertOk();
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
        $response->assertSee('Disallow: /platform', false);
        $response->assertSee('Disallow: /workspace', false);
    }

    public function test_missing_page_returns_noindex_error_view(): void
    {
        $response = $this->get('/definitely-missing-'.uniqid());

        $response->assertStatus(404);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
