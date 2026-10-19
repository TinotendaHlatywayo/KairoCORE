<?php

namespace Tests\Feature;

use App\Support\HelpCentre\HelpCentreRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Public Help Centre (/docs): unauthenticated access, rendering, search,
 * 404s for unknown content, sitemap inclusion, and no leakage of private
 * workspace routes into public pages.
 */
class HelpCentreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Help articles are plain Markdown files — no database needed for
        // rendering. The private-route guard test does touch sessions, so
        // point the default connection at the local MySQL like sibling tests.
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        app(HelpCentreRepository::class)->flush();
    }

    public function test_help_centre_index_is_public_and_lists_categories(): void
    {
        $response = $this->get('/docs');

        $response->assertOk();
        $response->assertSee('Help Centre');
        $response->assertSee('Getting started', false);

        // Must not require authentication.
        $this->assertGuest();
    }

    public function test_category_page_lists_published_articles(): void
    {
        $response = $this->get('/docs/finance');

        $response->assertOk();
        $response->assertSee('Fee structures', false);
        $response->assertSee('Record payments', false);
    }

    public function test_article_page_renders_markdown_and_breadcrumbs(): void
    {
        $response = $this->get('/docs/getting-started/create-account-and-sign-in');

        $response->assertOk();
        $response->assertSee('Create your school account', false);
        $response->assertSee('/register', false);
        $response->assertSee('Help Centre', false);
        $response->assertSee('Breadcrumb', false);
    }

    public function test_article_pages_carry_structured_data(): void
    {
        $html = $this->get('/docs/finance/recording-payments')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"TechArticle"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
    }

    public function test_search_returns_results_and_is_noindex(): void
    {
        $response = $this->get('/docs/search?q=invoice');

        $response->assertOk();
        $response->assertSee('invoice', false);
        $response->assertSee('noindex', false);
    }

    public function test_search_with_no_matches_shows_friendly_state(): void
    {
        $response = $this->get('/docs/search?q=zzzzzznotfoundzzzz');

        $response->assertOk();
        $response->assertSee('No guides matched', false);
    }

    public function test_unknown_article_returns_404(): void
    {
        $this->get('/docs/finance/this-article-does-not-exist')->assertNotFound();
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->get('/docs/no-such-category')->assertNotFound();
    }

    public function test_draft_articles_are_never_served(): void
    {
        $dir = resource_path('docs/troubleshooting');
        $draft = $dir.'/zz-draft-check.md';
        file_put_contents($draft, "---\ntitle: Secret draft\npublished: false\n---\n\n# Secret\n");

        try {
            app(HelpCentreRepository::class)->flush();
            $this->get('/docs/troubleshooting/zz-draft-check')->assertNotFound();
            $this->get('/docs/troubleshooting')->assertDontSee('Secret draft', false);
        } finally {
            unlink($draft);
            app(HelpCentreRepository::class)->flush();
        }
    }

    public function test_sitemap_includes_help_centre_and_articles(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/docs', $xml);
        $this->assertStringContainsString('/docs/getting-started/create-account-and-sign-in', $xml);
        $this->assertStringContainsString('/docs/finance/recording-payments', $xml);
    }

    public function test_llms_txt_links_to_help_centre(): void
    {
        $text = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringContainsString('/docs', $text);
        $this->assertStringContainsString('[Help Centre index]', $text);
    }

    public function test_private_workspace_and_platform_routes_stay_protected(): void
    {
        // The Help Centre must not (and does not) expose these; they still
        // redirect guests to login.
        $this->get('/workspace')->assertRedirect();
        $this->get('/platform')->assertRedirect();
    }

    public function test_help_pages_do_not_leak_private_paths(): void
    {
        foreach (['/docs', '/docs/finance', '/docs/getting-started/create-account-and-sign-in'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString('/workspace/', $html, "Private workspace path leaked on {$path}");
            $this->assertStringNotContainsString('/platform/', $html, "Private platform path leaked on {$path}");
        }
    }

    public function test_every_published_article_has_summary_and_renders(): void
    {
        $articles = app(HelpCentreRepository::class)->all();
        $this->assertNotEmpty($articles, 'No published help articles found');

        foreach ($articles as $article) {
            $this->assertNotSame('', $article->title, "Empty title on {$article->filePath}");
            $this->assertNotSame('', $article->summary, "Empty summary on {$article->filePath}");

            $response = $this->get($article->url());
            $response->assertOk();
            $response->assertSee($article->title, false);
        }
    }

    public function test_articles_survive_a_serialising_cache_store(): void
    {
        // HelpArticle has readonly properties; caching objects breaks
        // file/redis stores on unserialize (production 500s on /docs).
        // The repo must cache plain arrays and re-hydrate.
        app(HelpCentreRepository::class)->flush();
        Config::set('cache.default', 'file');

        $repo = app(HelpCentreRepository::class);
        $this->get('/docs')->assertOk(); // warms the file cache

        // Raw entry must be arrays, not serialized objects.
        $cached = Cache::store('file')->get('help-centre.articles');
        $this->assertIsArray($cached);
        $this->assertIsArray($cached[0] ?? null);

        // A fresh hydration from the cache must still yield real articles.
        app()->forgetInstance(HelpCentreRepository::class);
        $articles = app(HelpCentreRepository::class)->all();
        $this->assertNotEmpty($articles);
        $this->assertIsString($articles[0]->title);
        $this->assertNotEmpty($articles[0]->category);

        $this->get('/docs/finance')->assertOk();
        $this->get('/llms.txt')->assertOk();
    }
}
