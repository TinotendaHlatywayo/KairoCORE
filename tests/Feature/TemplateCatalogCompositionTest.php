<?php

namespace Tests\Feature;

use Modules\CMS\Services\SiteTemplateCatalog;
use Tests\TestCase;

/**
 * Home-page seed compositions differentiate the templates at a glance.
 * Community Warm leads with family voices, Playful Garden with photos,
 * Coastal Fresh with the round campus tour. All ten catalogs stay intact.
 */
class TemplateCatalogCompositionTest extends TestCase
{
    public function test_all_ten_template_catalogs_stay_intact(): void
    {
        $catalog = SiteTemplateCatalog::catalog();

        $this->assertCount(10, $catalog);

        foreach ($catalog as $key => $pages) {
            $this->assertArrayHasKey('home', $pages, $key);
            $this->assertNotEmpty($pages['home']['blocks'], $key);
        }
    }

    public function test_community_warm_leads_with_family_voices(): void
    {
        $types = $this->homeTypes('community-warm');

        $this->assertSame(['hero', 'testimonials', 'statistics'], array_slice($types, 0, 3));
    }

    public function test_playful_garden_is_photo_led(): void
    {
        $types = $this->homeTypes('playful-garden');

        $this->assertSame(['hero', 'gallery', 'marquee_ticker'], array_slice($types, 0, 3));
    }

    public function test_coastal_fresh_tours_campus_first(): void
    {
        $types = $this->homeTypes('coastal-fresh');

        $this->assertSame(['hero', 'orbit_gallery', 'marquee_ticker'], array_slice($types, 0, 3));
    }

    public function test_retired_dark_template_catalog_is_untouched(): void
    {
        // Rendering support stays; only selection hides it.
        $types = $this->homeTypes('neon-frontier');

        $this->assertSame('cinematic_scroll', $types[0]);
    }

    /** @return list<string> */
    private function homeTypes(string $template): array
    {
        return array_map(
            fn (array $block) => $block['type'],
            SiteTemplateCatalog::page($template, 'home')['blocks']
        );
    }
}
