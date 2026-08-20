<?php

namespace Tests\Feature;

use App\Filament\App\Pages\VisualCmsBuilder;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsSiteTemplate;
use Modules\CMS\Models\CmsWebsite;
use Tests\TestCase;

class CmsStudioThemeHubTest extends TestCase
{
    protected int $schoolId;

    protected CmsWebsite $live;

    protected array $snapshots = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Pin to the real MySQL test database (phpunit.xml defaults to sqlite).
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->schoolId = 15;
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);

        // The VisualCmsBuilder page is permission-gated (module: website,
        // permission: manage_pages), so tests must authenticate as the school
        // administrator (role bypasses the module permission check).
        $this->actingAs(User::findOrFail(13));

        $this->live = CmsWebsite::where('school_id', $this->schoolId)->where('is_template_site', false)->firstOrFail();

        // Snapshot the state we may mutate so we can restore it afterwards.
        $this->snapshots['website'] = $this->live->only([
            'active_template', 'active_site_template_id', 'color_primary', 'color_secondary',
            'color_accent', 'color_background', 'color_text', 'color_card_bg',
            'font_primary', 'font_secondary', 'design_radius', 'design_shadow',
            'design_container', 'design_button_style',
        ]);

        $this->snapshots['pages'] = CmsPage::where('cms_website_id', $this->live->id)
            ->get(['id', 'page_template', 'blocks', 'draft_blocks', 'is_homepage', 'is_published', 'hide_from_nav', 'sort_order'])
            ->toArray();

        $this->snapshots['site_templates'] = CmsSiteTemplate::where('school_id', $this->schoolId)
            ->get(['id', 'name', 'cms_website_id', 'description'])
            ->toArray();

        // Snapshot every template's shadow pages too (the isolation test deletes one).
        $shadowSiteIds = CmsSiteTemplate::where('school_id', $this->schoolId)->pluck('cms_website_id');
        $this->snapshots['shadow_pages'] = CmsPage::whereIn('cms_website_id', $shadowSiteIds)
            ->get(['id', 'school_id', 'cms_website_id', 'title', 'slug', 'page_template', 'blocks', 'draft_blocks', 'is_homepage', 'is_published', 'hide_from_nav', 'sort_order'])
            ->toArray();
    }

    protected function tearDown(): void
    {
        // Remove any templates created during the test, then restore snapshots.
        foreach (CmsSiteTemplate::where('school_id', $this->schoolId)->get() as $tpl) {
            $known = collect($this->snapshots['site_templates'])->contains('id', $tpl->id);
            if (! $known) {
                $tpl->website?->pages()->delete();
                $tpl->website?->delete();
                $tpl->delete();
            }
        }

        // Restore the live website (must reload via fresh(): a stale model whose
        // attributes match the snapshot would skip the UPDATE entirely).
        $this->live->fresh()->update($this->snapshots['website']);

        foreach ($this->snapshots['pages'] as $snap) {
            CmsPage::where('id', $snap['id'])->update($snap);
        }

        // Re-create any shadow pages that tests deleted from template drafts.
        foreach ($this->snapshots['shadow_pages'] as $snap) {
            if (! CmsPage::where('id', $snap['id'])->exists()) {
                CmsPage::create($snap);
            }
        }

        parent::tearDown();
    }

    public function test_theme_switch_propagates_to_all_following_pages(): void
    {
        $home = CmsPage::where('cms_website_id', $this->live->id)->where('is_homepage', true)->firstOrFail();

        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $component->call('setActiveTemplate', 'cinematic-immersive');

        $this->assertSame('cinematic-immersive', $component->get('activeTemplate'));
        $this->assertSame('cinematic-immersive', $component->get('pageTemplate'));
        $this->assertSame('cinematic-immersive', $this->live->fresh()->active_template);

        // All pages pinned to the old site-wide template followed the switch.
        $stale = CmsPage::where('cms_website_id', $this->live->id)
            ->whereIn('page_template', ['heritage-editorial', 'modern-vibrant', 'minimalist-academic', 'community-warm', 'home_4', null])
            ->whereNot('page_template', 'cinematic-immersive')
            ->count();
        $this->assertSame(0, $stale, 'Some pages still reference the previous template after the switch.');
    }

    public function test_hub_lists_all_premade_templates(): void
    {
        $home = CmsPage::where('cms_website_id', $this->live->id)->where('is_homepage', true)->firstOrFail();

        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $premade = $component->get('premadeTemplates');

        $this->assertCount(10, $premade, 'Hub should expose exactly 10 premade templates.');

        $names = collect($premade)->pluck('name')->sort()->values()->all();
        $this->assertSame([
            'Cinematic Immersive',
            'Coastal Fresh',
            'Community Warm',
            'Emerald Heritage',
            'Heritage Editorial',
            'Minimalist Academic',
            'Modern Vibrant',
            'Neon Frontier',
            'Playful Garden',
            'Sunset International',
        ], $names);

        // Live site has no active template id right now (editing live directly).
        $this->assertNull($component->get('siteTemplateId'));
    }

    public function test_premade_templates_cannot_be_deleted(): void
    {
        $home = CmsPage::where('cms_website_id', $this->live->id)->where('is_homepage', true)->firstOrFail();
        $premade = CmsSiteTemplate::where('school_id', $this->schoolId)->orderBy('id')->firstOrFail();

        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $component->call('deleteSiteTemplate', $premade->id);

        $this->assertDatabaseHas('cms_site_templates', ['id' => $premade->id]);
    }

    public function test_site_navigator_delete_is_isolated_to_template_draft(): void
    {
        $premade = CmsSiteTemplate::where('school_id', $this->schoolId)->where('name', 'Heritage Editorial')->firstOrFail();
        $shadow = $premade->website;
        $shadowPages = CmsPage::where('cms_website_id', $shadow->id)->get();
        $this->assertGreaterThan(0, $shadowPages->count());

        $target = $shadowPages->firstWhere('is_homepage', false);
        $home = $shadowPages->firstWhere('is_homepage', true) ?? $shadowPages->first();
        $this->assertNotNull($target);

        // Open the studio on the template draft (shadow website) and delete a page.
        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $this->assertSame($premade->id, $component->get('siteTemplateId'));

        $component->call('deletePage', $target->id);
        $this->assertDatabaseMissing('cms_pages', ['id' => $target->id]);

        // The premade template's own page list is untouched (shadow website only).
        $remaining = CmsPage::where('cms_website_id', $shadow->id)->count();
        $this->assertSame($shadowPages->count() - 1, $remaining);

        // The live site still has all its pages intact.
        $liveCount = CmsPage::where('cms_website_id', $this->live->id)->count();
        $this->assertSame(count($this->snapshots['pages']), $liveCount);
    }

    public function test_apply_template_sets_active_template_on_live(): void
    {
        $home = CmsPage::where('cms_website_id', $this->live->id)->where('is_homepage', true)->firstOrFail();
        $cinematic = CmsSiteTemplate::where('school_id', $this->schoolId)->where('name', 'Cinematic Immersive')->firstOrFail();

        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $component->call('applyTemplate', $cinematic->id);

        $this->assertSame($cinematic->id, $this->live->fresh()->active_site_template_id);
        $this->assertSame('cinematic-immersive', $this->live->fresh()->active_template);
    }

    public function test_public_catchall_never_captures_workspace_panel(): void
    {
        $router = app('router');
        $tenantHost = 'tinwayacademy.'.parse_url(config('app.url'), PHP_URL_HOST);

        $workspacePaths = ['/workspace', '/workspace/login', '/workspace/cms/builder/172', '/workspace/cms-websites'];
        foreach ($workspacePaths as $path) {
            $request = Request::create($path, 'GET');
            $request->headers->set('Host', $tenantHost);
            $route = $router->getRoutes()->match($request);
            $this->assertNotSame('cms-render', $route->getName(), "{$path} must not be captured by the CMS catch-all");
        }

        // Public CMS pages still resolve through the catch-all.
        $request = Request::create('/about', 'GET');
        $request->headers->set('Host', $tenantHost);
        $this->assertSame('cms-render', $router->getRoutes()->match($request)->getName());
    }

    public function test_editing_coverflow_photo_size_keeps_section_in_preview(): void
    {
        $template = CmsSiteTemplate::where('school_id', $this->schoolId)->where('name', 'Cinematic Immersive')->firstOrFail();
        $home = CmsPage::where('cms_website_id', $template->cms_website_id)->where('is_homepage', true)->firstOrFail();

        $index = null;
        foreach ($home->blocks as $i => $block) {
            if (($block['type'] ?? '') === 'coverflow_carousel') {
                $index = $i;
                break;
            }
        }
        $this->assertNotNull($index, 'Cinematic home must contain a coverflow_carousel block');

        $component = Livewire::test(VisualCmsBuilder::class, ['pageId' => $home->id]);
        $component->call('selectBlock', $index);

        // The section must already be rendered in the studio canvas with the
        // size hook (and its x-cloak present server-side for Alpine to reveal).
        $this->assertStringContainsString('sc-coverflow-stage', $component->html());
        $this->assertStringContainsString('data-sc-card-width', $component->html());

        // Editing the photo size must not make the section disappear from the preview.
        $component->set('selectedBlockData.card_width', 500);
        $component->set('selectedBlockData.card_height', 340);
        $component->call('updateBlockSettings');

        $blocks = $component->get('blocks');
        $this->assertSame(500, $blocks[$index]['card_width'] ?? null);
        $this->assertSame(340, $blocks[$index]['card_height'] ?? null);
        $this->assertStringContainsString('sc-coverflow-stage', $component->html());
        $this->assertStringContainsString('data-sc-card-width="500"', $component->html());
        $this->assertStringContainsString('Carousel Photo Size', $component->html());

        // Apply & Save must push the resized photos through to the live site.
        $component->call('applyCustomizations');
        $liveBlock = null;
        foreach ($this->live->fresh()->pages()->where('is_homepage', true)->first()->blocks as $block) {
            if (($block['type'] ?? '') === 'coverflow_carousel') {
                $liveBlock = $block;
                break;
            }
        }
        $this->assertSame(500, $liveBlock['card_width'] ?? null, 'Live site must receive the resized coverflow photos');
        $this->assertSame(340, $liveBlock['card_height'] ?? null);
    }
}
