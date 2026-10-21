<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Filament\Admin\Pages\WebsiteContentPage;
use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Website Content is now a standalone sidebar page (the old Platform Settings
 * tab): Home / Features / About / Contact / Global & SEO tabs stay put, Blog
 * management joins the same tab strip, and Platform Settings loses the tab.
 *
 * No sqlite driver in this environment — runs against the local MySQL DB with
 * manual cleanup (same pattern as PlatformBlogTest).
 */
class WebsiteContentPageTest extends TestCase
{
    /** @var int[] */
    private array $createdPostIds = [];

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

        if (! Schema::hasTable('platform_blog_posts')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        config()->set('app.env', 'local');
    }

    protected function tearDown(): void
    {
        if ($this->createdPostIds !== []) {
            BlogPost::forceDestroy($this->createdPostIds);
        }

        parent::tearDown();
    }

    private function actingAsPlatformAdmin(): User
    {
        $user = User::withoutTenantScope()
                ->where('email', 'tinhlats09@gmail.com')
                ->whereNull('school_id')
                ->first()
            ?? User::withoutTenantScope()
                ->whereNull('school_id')
                ->where('account_status', 'active')
                ->orderBy('id')
                ->firstOrFail();

        $this->actingAs($user);

        return $user;
    }

    public function test_website_content_page_renders_with_section_and_blog_tabs(): void
    {
        $this->actingAsPlatformAdmin();

        $response = $this->get('https://lvh.me/platform/website-content');
        $response->assertOk();

        // assertSee escapes the needle, so "Global & SEO" matches its &amp; form.
        foreach (['Home', 'Features', 'About', 'Contact', 'Global & SEO', 'Blog'] as $tab) {
            $response->assertSee($tab);
        }

        // Blog tab defaults to its post list.
        $response->assertSee('Articles published on the public /blog page');
    }

    public function test_platform_settings_page_lost_the_website_content_tab(): void
    {
        $this->actingAsPlatformAdmin();

        $content = $this->get('https://lvh.me/platform/platform-settings-page')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'Public website content',
            $content,
            'Platform Settings still renders the Website Content tab intro'
        );
        $this->assertStringNotContainsString(
            'Articles published on the public /blog page',
            $content,
            'Platform Settings should not contain the Blog tab'
        );
    }

    public function test_blog_resource_no_longer_registers_a_sidebar_item(): void
    {
        $this->assertFalse(BlogPostResource::shouldRegisterNavigation());
    }

    public function test_blog_crud_through_the_website_content_page(): void
    {
        $this->actingAsPlatformAdmin();

        $page = Livewire::test(WebsiteContentPage::class);

        // ── Create ──
        $page->call('startBlogCreate');
        $page->assertSet('blogMode', 'create')
            ->assertSet('blogEditingId', null);

        $page->set('data.blog.title', 'Page CRUD Title');
        // The title update auto-slugs while the field is live.
        $page->assertSet('data.blog.slug', 'page-crud-title');

        $page->set('data.blog.content', '<p>Body with <a href="/features">a link</a>.</p>')
            ->set('data.blog.status', BlogPost::STATUS_PUBLISHED)
            ->set('data.blog.seo_description', 'A meta description.');

        $page->call('saveBlogPostAndReturn');

        $post = BlogPost::query()
            ->where('slug', 'page-crud-title')
            ->firstOrFail();
        $this->createdPostIds[] = $post->id;

        $this->assertSame('Page CRUD Title', $post->title);
        $this->assertSame(BlogPost::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at, 'publishing without a date must stamp one');
        $page->assertSet('blogMode', 'list');

        // ── Read (list shows the post) ──
        $page->call('blogEdit', $post->id);
        $page->assertSet('blogMode', 'edit')
            ->assertSet('blogEditingId', $post->id)
            ->assertSet('data.blog.title', 'Page CRUD Title');

        // ── Update ──
        $page->set('data.blog.title', 'Renamed Post')
            ->set('data.blog.status', BlogPost::STATUS_DRAFT);
        $page->call('persistBlogPost');

        $post->refresh();
        $this->assertSame('Renamed Post', $post->title);
        $this->assertSame(BlogPost::STATUS_DRAFT, $post->status);
        $this->assertSame('renamed-post', $post->slug, 'blanked/renamed title must re-slug');

        // ── Delete ──
        $page->call('blogDelete', $post->id);
        $page->assertSet('blogMode', 'list');
        $this->assertSoftDeleted('platform_blog_posts', ['id' => $post->id]);
    }

    public function test_main_save_while_editing_a_post_persists_the_post(): void
    {
        $this->actingAsPlatformAdmin();

        $page = Livewire::test(WebsiteContentPage::class);
        $page->call('startBlogCreate');
        $page->set('data.blog.title', 'Saved By Main Button')
            ->set('data.blog.content', '<p>x</p>')
            ->set('data.blog.status', BlogPost::STATUS_PUBLISHED);

        $page->call('save');

        $post = BlogPost::query()->where('slug', 'saved-by-main-button')->firstOrFail();
        $this->createdPostIds[] = $post->id;

        $this->assertSame(BlogPost::STATUS_PUBLISHED, $post->status);
        $page->assertSet('blogMode', 'create', 'main Save keeps the editor open for further edits');
        $page->assertSet('blogEditingId', $post->id, 'a freshly created post must become the editing target');
    }
}
