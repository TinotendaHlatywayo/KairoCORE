<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsTemplateService;
use Tests\TestCase;

/**
 * Regression: a site-wide template switch must also update pages whose
 * page_theme holds a legacy raw key (e.g. modern_international) that
 * resolves to the previous template — not just pages whose raw column
 * value exactly matches the canonical key.
 *
 * Tests the matching logic used by VisualCmsBuilder::setActiveTemplate().
 */
class CmsTemplateSwitchLegacyKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_legacy_key_resolves_to_the_same_template_as_the_canonical_key(): void
    {
        // modern_international is a legacy raw key that maps to heritage-editorial.
        $resolved = CmsTemplateService::resolvePageTheme('modern_international', 'modern_international', 'heritage-editorial');
        $this->assertSame('heritage-editorial', $resolved);

        // The canonical key resolves to itself.
        $this->assertSame('heritage-editorial', CmsTemplateService::resolvePageTheme('heritage-editorial', null, 'heritage-editorial'));

        // An explicit per-page override to a different template resolves to that template.
        $this->assertSame('coastal-fresh', CmsTemplateService::resolvePageTheme('coastal-fresh', null, 'heritage-editorial'));
    }

    public function test_switch_matching_includes_legacy_keyed_pages_and_preserves_overrides(): void
    {
        DB::beginTransaction();

        try {
            // The CmsPage::saved observer calls route('tenant.home') which
            // requires the {tenant} domain parameter.
            URL::defaults(['tenant' => 'testschool']);

            $school = School::create([
                'name' => 'Legacy Switch Test '.uniqid(),
                'subdomain' => 'legacy-sw-'.substr(uniqid(), -6),
                'status' => 'active',
                'institution_type' => 'primary',
            ]);

            $website = CmsWebsite::create([
                'school_id' => $school->id,
                'active_template' => 'heritage-editorial',
            ]);

            // Legacy back-fill left this raw key in page_theme.
            $legacyPage = CmsPage::create([
                'school_id' => $school->id,
                'cms_website_id' => $website->id,
                'title' => 'Home',
                'slug' => 'home',
                'is_homepage' => true,
                'page_theme' => 'modern_international',
                'page_template' => 'modern_international',
                'blocks' => [],
            ]);

            // Explicit per-page override to a different template (must survive the switch).
            $overridePage = CmsPage::create([
                'school_id' => $school->id,
                'cms_website_id' => $website->id,
                'title' => 'About',
                'slug' => 'about',
                'page_theme' => 'playful-garden',
                'page_template' => 'playful-garden',
                'blocks' => [],
            ]);

            // A page with no page_theme (follows site default).
            $defaultPage = CmsPage::create([
                'school_id' => $school->id,
                'cms_website_id' => $website->id,
                'title' => 'Contact',
                'slug' => 'contact',
                'page_theme' => '',
                'page_template' => '',
                'blocks' => [],
            ]);

            // Replicate the matching logic from setActiveTemplate().
            $previousTemplate = CmsTemplateService::canonicalTemplate($website->active_template);
            $newTemplate = 'coastal-fresh';

            $pageIdsToSwitch = CmsPage::where('cms_website_id', $website->id)
                ->get(['id', 'page_theme', 'page_template'])
                ->filter(fn ($p) => CmsTemplateService::resolvePageTheme($p->page_theme, $p->page_template, $website->active_template) === $previousTemplate)
                ->pluck('id');

            // The legacy-keyed page AND the default page must be in the switch set.
            $this->assertTrue($pageIdsToSwitch->contains($legacyPage->id), 'legacy-keyed page must be switched');
            $this->assertTrue($pageIdsToSwitch->contains($defaultPage->id), 'default page must be switched');
            $this->assertFalse($pageIdsToSwitch->contains($overridePage->id), 'explicit override page must be preserved');

            // Apply the switch.
            CmsPage::whereIn('id', $pageIdsToSwitch)
                ->update(['page_theme' => $newTemplate, 'page_template' => $newTemplate]);

            $legacyPage->refresh();
            $overridePage->refresh();
            $defaultPage->refresh();

            $this->assertSame('coastal-fresh', $legacyPage->page_theme);
            $this->assertSame('coastal-fresh', $defaultPage->page_theme);
            $this->assertSame('playful-garden', $overridePage->page_theme, 'explicit override must survive the site-wide switch');
        } finally {
            DB::rollBack();
        }
    }
}
