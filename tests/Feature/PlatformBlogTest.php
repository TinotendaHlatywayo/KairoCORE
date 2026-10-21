<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Platform blog: index + post pages only serve published posts, drafts are
 * invisible (404), and the body is sanitised with the blog allowlist — images
 * and links survive, scripts and event handlers never do.
 *
 * No sqlite driver in this environment, so tests run against the local MySQL
 * DB with manual cleanup (same pattern as the CMS feature tests).
 */
class PlatformBlogTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        if ($this->createdPostIds !== []) {
            BlogPost::forceDestroy($this->createdPostIds);
        }

        parent::tearDown();
    }

    private function makePost(array $overrides = []): BlogPost
    {
        $post = BlogPost::create(array_merge([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'excerpt' => 'A short summary.',
            'content' => '<p>Body with a <a href="/features">link</a>.</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));

        $this->createdPostIds[] = $post->id;

        return $post;
    }

    public function test_blog_index_lists_published_posts_and_hides_drafts(): void
    {
        $published = $this->makePost();
        $this->makePost([
            'title' => 'Secret Draft',
            'slug' => 'secret-draft',
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $response = $this->get(route('marketing.blog'));
        $response->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Secret Draft');
    }

    public function test_published_post_renders_sanitised_content(): void
    {
        $post = $this->makePost([
            'content' => '<h2>Section</h2>'
                .'<p>Hey <a href="/security">security team</a>, '
                .'visit <a href="javascript:alert(1)">bad</a>.</p>'
                .'<img src="/images/placeholders/news-cover.svg" alt="cover">'
                .'<script>alert("xss")</script>'
                .'<p onclick="steal()">Click</p>',
        ]);

        $html = $this->get(route('marketing.blog.show', ['slug' => $post->slug]))
            ->assertOk()
            ->assertSee('Section', false)
            ->assertSee('href="/security"', false)
            ->assertSee('src="/images/placeholders/news-cover.svg"', false)
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('alert("xss")', false)
            ->assertDontSee('onclick="steal()"', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, '<img src="/images/placeholders/news-cover.svg"'));
    }

    public function test_draft_post_returns_404(): void
    {
        $post = $this->makePost([
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->get(route('marketing.blog.show', ['slug' => $post->slug]))->assertNotFound();
    }

    public function test_future_dated_post_is_not_served_yet(): void
    {
        $post = $this->makePost(['published_at' => now()->addDay()]);

        $this->get(route('marketing.blog.show', ['slug' => $post->slug]))->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->makePost();

        $this->get(route('marketing.blog.show', ['slug' => 'no-such-post']))->assertNotFound();
    }

    public function test_blog_appears_in_sitemap_and_llms(): void
    {
        $post = $this->makePost();

        $sitemap = $this->get(route('marketing.sitemap'))->assertOk()->getContent();
        $this->assertStringContainsString('/blog/'.$post->slug, $sitemap);

        $llms = $this->get(route('marketing.llms'))->assertOk()->getContent();
        $this->assertStringContainsString('/blog', $llms);
    }

    public function test_attachment_json_figures_are_resolved_to_plain_images(): void
    {
        $attachment = e(json_encode([
            'url' => '/storage/platform/blog/attachments/plan.pdf',
            'filename' => 'plan.pdf',
            'contentType' => 'application/pdf',
        ]));
        $image = e(json_encode([
            'url' => '/storage/platform/blog/attachments/pic.png',
            'filename' => 'pic.png',
            'contentType' => 'image/png',
        ]));

        $html = BlogPost::resolveAttachments(
            '<figure data-trix-attachment="'.$attachment.'"></figure>'
            .'<figure data-trix-attachment="'.$image.'"></figure>'
        );

        $this->assertStringContainsString('<a href="/storage/platform/blog/attachments/plan.pdf"', $html);
        $this->assertStringContainsString('<img src="/storage/platform/blog/attachments/pic.png"', $html);
        $this->assertStringNotContainsString('trix-attachment', $html);
    }

    public function test_default_sanitizer_still_blocks_images_but_blog_allowlist_keeps_them(): void
    {
        $img = '<p><img src="/x.png" alt="x"> hi</p>';

        $this->assertSame(
            '<p> hi</p>',
            \Modules\CMS\Services\CmsTemplateService::sanitizeRichText($img)
        );
        $this->assertSame(
            '<p><img src="/x.png" loading="lazy" alt="x"> hi</p>',
            \Modules\CMS\Services\CmsTemplateService::sanitizeRichText($img, BlogPost::CONTENT_ALLOWED_TAGS)
        );
    }

    public function test_blog_nav_links_exist_on_the_home_page(): void
    {
        $home = $this->get(route('marketing.home'))->assertOk();

        $home->assertSee(route('marketing.blog'));
        $this->assertStringContainsString(
            'href="'.route('marketing.blog').'"',
            $home->getContent()
        );
    }

    public function test_slug_is_generated_from_title_when_blank(): void
    {
        $post = BlogPost::create([
            'title' => 'Campus Roles & Consolidated Teacher Homes',
            'slug' => '',
            'content' => '<p>x</p>',
            'status' => BlogPost::STATUS_DRAFT,
        ]);
        $this->createdPostIds[] = $post->id;

        $this->assertSame(
            Str::slug('Campus Roles & Consolidated Teacher Homes'),
            $post->slug
        );
    }

    public function test_cover_image_disk_paths_resolve_to_public_storage_urls(): void
    {
        $post = $this->makePost(['cover_image' => 'platform/blog/covers/example.webp']);

        $this->assertSame('/storage/platform/blog/covers/example.webp', $post->coverUrl());

        $this->get(route('marketing.blog'))
            ->assertOk()
            ->assertSee('/storage/platform/blog/covers/example.webp', false);

        $this->get(route('marketing.blog.show', ['slug' => $post->slug]))
            ->assertOk()
            ->assertSee('/storage/platform/blog/covers/example.webp', false);

        // Absolute paths and full URLs are used as-is.
        $absolute = new BlogPost(['cover_image' => 'https://cdn.example.com/x.jpg']);
        $this->assertSame('https://cdn.example.com/x.jpg', $absolute->coverUrl());

        $this->assertSame(
            '/images/placeholders/news-cover.svg',
            (new BlogPost(['cover_image' => null]))->coverUrl()
        );
    }

    public function test_admin_list_and_create_pages_render_for_platform_admin(): void
    {
        config()->set('app.env', 'local');

        $user = \App\Models\User::withoutTenantScope()
            ->whereNull('school_id')
            ->where('account_status', 'active')
            ->orderBy('id')
            ->firstOrFail();
        $this->actingAs($user);

        $seeded = BlogPost::query()->first();

        $list = $this->get('https://lvh.me/platform/blog-posts')->assertOk();
        if ($seeded) {
            $list->assertSee($seeded->title);
        }

        $this->get('https://lvh.me/platform/blog-posts/create')->assertOk();
    }
}
