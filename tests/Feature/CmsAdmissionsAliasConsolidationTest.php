<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Tests\TestCase;

/**
 * 'admissions' and 'apply-online' are aliases for one page. Past races left
 * several copies on the live site, which the nav then rendered as many
 * Admissions links. A unique (website, slug) index now blocks new races, and
 * consolidation collapses any alias pair to one row.
 */
class CmsAdmissionsAliasConsolidationTest extends TestCase
{
    private int $schoolId;

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

        // The CMS nav-sync hook builds route('cms-render'/'tenant.home') URLs,
        // which need the tenant route parameter the middleware normally sets.
        URL::defaults(['tenant' => 'alias-test']);

        $uniq = uniqid();
        $this->schoolId = School::create([
            'name' => 'Alias Consolidation Test '.$uniq,
            'slug' => 'alias-test-'.$uniq,
            'subdomain' => 'alias-test-'.$uniq,
            'address' => '1 Test Street',
            'phone' => '000000000',
            'email' => 'alias-test@example.com',
        ])->id;
    }

    protected function tearDown(): void
    {
        CmsPage::withoutGlobalScopes()->where('school_id', $this->schoolId)->delete();
        CmsWebsite::withoutGlobalScopes()->where('school_id', $this->schoolId)->delete();
        School::withoutGlobalScopes()->where('id', $this->schoolId)->delete();

        parent::tearDown();
    }

    private function makeWebsite(): CmsWebsite
    {
        return CmsWebsite::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'is_template_site' => false,
            'active_template' => 'urban-creative',
        ]);
    }

    private function makePage(CmsWebsite $website, string $slug, array $extra = []): CmsPage
    {
        $page = CmsPage::create(array_merge([
            'school_id' => $this->schoolId,
            'cms_website_id' => $website->id,
            'title' => 'Admissions',
            'slug' => $slug,
            'is_published' => true,
            'blocks' => [],
        ], $extra));

        // Eloquent overwrites updated_at on save — backdate via query when asked.
        if (isset($extra['updated_at'])) {
            CmsPage::withoutGlobalScopes()->where('id', $page->id)->update(['updated_at' => $extra['updated_at']]);
            $page->refresh();
        }

        return $page;
    }

    public function test_consolidate_keeps_most_recent_page_when_both_alias_slugs_exist(): void
    {
        $website = $this->makeWebsite();

        $this->makePage($website, 'apply-online', ['updated_at' => now()->subHour()]);
        $recent = $this->makePage($website, 'admissions', ['updated_at' => now()]);

        $canonical = CmsPage::consolidateAdmissionsAlias($website->id);

        $this->assertNotNull($canonical);
        $this->assertSame($recent->id, $canonical->id);
        $this->assertSame('apply-online', $canonical->slug);
        $this->assertSame(1, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->whereIn('slug', ['apply-online', 'admissions'])->count());
    }

    public function test_consolidate_keeps_apply_online_when_it_is_the_most_recent(): void
    {
        $website = $this->makeWebsite();

        $this->makePage($website, 'admissions', ['updated_at' => now()->subHour()]);
        $kept = $this->makePage($website, 'apply-online', ['updated_at' => now()]);

        $canonical = CmsPage::consolidateAdmissionsAlias($website->id);

        $this->assertSame($kept->id, $canonical->id);
        $this->assertSame('apply-online', $canonical->slug);
        $this->assertSame(1, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->whereIn('slug', ['apply-online', 'admissions'])->count());
    }

    public function test_consolidate_returns_null_when_no_alias_pages(): void
    {
        $website = $this->makeWebsite();
        $this->makePage($website, 'about');

        $this->assertNull(CmsPage::consolidateAdmissionsAlias($website->id));
        $this->assertSame(1, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->count());
    }

    public function test_database_rejects_duplicate_slug_on_same_website(): void
    {
        $website = $this->makeWebsite();
        $this->makePage($website, 'apply-online');

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->makePage($website, 'apply-online');
    }

    public function test_database_allows_same_slug_on_different_websites(): void
    {
        $one = $this->makeWebsite();
        $two = $this->makeWebsite();

        $this->makePage($one, 'apply-online');
        $this->makePage($two, 'apply-online');

        $this->assertSame(2, CmsPage::withoutGlobalScopes()->where('slug', 'apply-online')->whereIn('cms_website_id', [$one->id, $two->id])->count());
    }

    public function test_ensure_all_standard_pages_does_not_recreate_admissions_duplicate(): void
    {
        $website = $this->makeWebsite();
        // The canonical page already exists as apply-online; the catalog key
        // for some templates is 'admissions'. ensureAllStandardPages must
        // recognise the alias and not create a second nav entry.
        $this->makePage($website, 'apply-online');

        \Modules\CMS\Http\Controllers\CmsRenderController::ensureAllStandardPages($website, $this->schoolId);

        $this->assertSame(1, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->whereIn('slug', ['admissions', 'apply-online'])->count());
    }

    public function test_ensure_all_standard_pages_creates_canonical_apply_online_slug(): void
    {
        $website = $this->makeWebsite();

        \Modules\CMS\Http\Controllers\CmsRenderController::ensureAllStandardPages($website, $this->schoolId);

        $this->assertSame(1, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->where('slug', 'apply-online')->count());
        $this->assertSame(0, CmsPage::withoutGlobalScopes()->where('cms_website_id', $website->id)->where('slug', 'admissions')->count());
    }
}
