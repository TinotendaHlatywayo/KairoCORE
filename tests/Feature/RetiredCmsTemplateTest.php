<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Services\CmsTemplateService;
use Modules\CMS\Services\ComponentRegistry;
use Tests\TestCase;

/**
 * The dark template (Neon Frontier) is retired from new selection but must
 * keep resolving for existing websites. Pickers and premade seeding use
 * selectableTemplates(); renderers keep using getTemplates().
 */
class RetiredCmsTemplateTest extends TestCase
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

    public function test_dark_template_is_retired_from_selection(): void
    {
        $this->assertContains('neon-frontier', ComponentRegistry::RETIRED_TEMPLATE_KEYS);
        $this->assertArrayNotHasKey('neon-frontier', CmsTemplateService::selectableTemplates());
        $this->assertTrue(CmsTemplateService::isRetired('neon-frontier'));
        $this->assertFalse(CmsTemplateService::isRetired('heritage-editorial'));
    }

    public function test_existing_sites_keep_resolving_the_dark_template(): void
    {
        // Renderers and theme resolution still see the full set.
        $this->assertArrayHasKey('neon-frontier', CmsTemplateService::getTemplates());

        // Stored keys keep canonicalising to themselves (no silent migration).
        $this->assertSame('neon-frontier', CmsTemplateService::canonicalTemplate('neon-frontier'));
    }

    public function test_every_selectable_template_still_has_full_tokens(): void
    {
        foreach (CmsTemplateService::selectableTemplates() as $key => $tpl) {
            $this->assertNotEmpty($tpl['name'], $key);
            $this->assertNotEmpty($tpl['palette']['primary'], $key);
            $this->assertNotEmpty($tpl['fonts']['primary'], $key);
            $this->assertNotEmpty($tpl['design']['radius'], $key);
        }

        // 14 selectable: 9 original + 5 Simple CMS (nordic-light, savanna-gold,
        // botanical-green, mediterranean-blue, urban-creative). 15 total including
        // retired neon-frontier.
        $this->assertCount(14, CmsTemplateService::selectableTemplates());
        $this->assertCount(15, CmsTemplateService::getTemplates());
        $this->assertCount(5, CmsTemplateService::simpleTemplates());
    }

    public function test_builder_pickers_offer_only_selectable_templates(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/cms/visual-cms-builder.blade.php'));

        // Every picker loop must use the selectable set.
        $pickerCount = substr_count($blade, 'CmsTemplateService::selectableTemplates()');
        $this->assertGreaterThanOrEqual(4, $pickerCount);

        // The one remaining getTemplates() call resolves a stored page-theme
        // name for display and must keep seeing retired templates.
        $this->assertStringContainsString('getTemplates()[$p[\'page_theme\']]', $blade);
    }
}
