<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\CMS\Services\CmsTemplateService;

/**
 * Platform-level blog post published on the central marketing site (kairo.co.zw).
 * Not tenant-scoped: this content belongs to the platform, not any school.
 */
class BlogPost extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /**
     * Tags a blog body may contain on output — the shared rich-text allowlist
     * plus the editorial extras (headings, images, preformatted blocks).
     * Anything else is stripped by sanitizeRichText() on render.
     */
    public const CONTENT_ALLOWED_TAGS = [
        'p', 'div', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
        'sub', 'sup', 'span', 'font', 'ul', 'ol', 'li', 'a', 'blockquote',
        'code', 'h3', 'h4', 'h5',
        'h1', 'h2', 'h6', 'hr', 'img', 'figure', 'figcaption', 'pre',
    ];

    protected $table = 'platform_blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'published_at',
        'seo_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if (blank($post->slug)) {
                $post->slug = Str::slug((string) $post->title);
            }
            if ($post->status === self::STATUS_PUBLISHED && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    /** Body HTML with the blog allowlist applied (safe for {!! !!} output). */
    public function renderContent(): string
    {
        return CmsTemplateService::sanitizeRichText($this->content, self::CONTENT_ALLOWED_TAGS);
    }

    public function coverUrl(): string
    {
        return $this->cover_image ?: '/images/placeholders/news-cover.svg';
    }

    /**
     * The rich editor serialises uploaded files as <figure data-trix-attachment='…'>
     * JSON blobs that only render inside the editor. Convert them to plain
     * <img>/<a> markup so the public post page shows them without any JS.
     */
    public static function resolveAttachments(string $html): string
    {
        return (string) preg_replace_callback(
            '#<figure[^>]*\bdata-trix-attachment\s*=\s*"([^"]*)"[^>]*>.*?</figure>#is',
            function (array $match): string {
                $attachment = json_decode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

                if (! is_array($attachment) || empty($attachment['url'])) {
                    return '';
                }

                $url = (string) $attachment['url'];
                $name = (string) ($attachment['filename'] ?? 'attachment');
                $type = (string) ($attachment['contentType'] ?? '');

                if (str_starts_with($type, 'image/')) {
                    return '<img src="'.e($url).'" alt="'.e($name).'" loading="lazy">';
                }

                return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.e($name).'</a>';
            },
            $html
        );
    }

    /**
     * Normalise raw form state into attributes safe for create/update.
     * Shared by the resource pages and the Website Content page so every
     * entry point applies the same slug / publish-date / attachment rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareAttributes(array $data, ?\DateTimeInterface $existingPublishedAt = null): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? ''));
        }

        if (($data['status'] ?? null) === self::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = $existingPublishedAt ?? now();
        }

        $data['content'] = self::resolveAttachments((string) ($data['content'] ?? ''));

        return $data;
    }
}
