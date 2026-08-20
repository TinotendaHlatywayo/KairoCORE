<?php

namespace Modules\CMS\Models;

use App\Filament\App\Pages\VisualCmsBuilder;
use App\Models\School;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\CMS\Services\CmsNavigationService;

/**
 * @property int $id
 * @property int $school_id
 * @property int $cms_website_id
 * @property string $title
 * @property string $slug
 * @property int|null $version
 * @property bool $is_published
 * @property bool $is_homepage
 * @property bool $hide_from_nav
 * @property array $blocks
 * @property array|null $draft_blocks
 * @property int $sort_order
 * @property string|null $page_template
 * @property array|null $page_settings
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property string|null $published_at
 * @property int|null $published_by
 */
class CmsPage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'cms_website_id',
        'title',
        'slug',
        'parent_slug',
        'sort_order',
        'depth',
        'is_published',
        'is_homepage',
        'is_protected',
        'password',
        'hide_from_nav',
        'hide_from_sitemap',
        'page_template',
        'page_layout',
        'blocks',
        'draft_blocks',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
        'seo_twitter_card',
        'seo_structured_data',
        'canonical_url',
        'noindex',
        'nofollow',
        'og_type',
        'og_locale',
        'version',
        'published_at',
        'published_by',
        'custom_css',
        'custom_js',
        'page_settings',
        'scripts',
    ];

    protected $casts = [
        'blocks' => 'array',
        'draft_blocks' => 'array',
        'page_layout' => 'array',
        'seo_structured_data' => 'array',
        'page_settings' => 'array',
        'scripts' => 'array',
        'is_published' => 'boolean',
        'is_homepage' => 'boolean',
        'is_protected' => 'boolean',
        'hide_from_nav' => 'boolean',
        'hide_from_sitemap' => 'boolean',
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * 'admissions' and 'apply-online' are aliases for one single admissions page.
     * Keep exactly one record per website, preferring 'apply-online' as the
     * canonical slug, and return it (or null when none exists).
     */
    public static function consolidateAdmissionsAlias(int $cmsWebsiteId): ?self
    {
        // Older sites can contain both alias rows. Keep the page most recently
        // edited, rather than blindly keeping `apply-online` and throwing away
        // the admission page content a user has just changed in the builder.
        $canonical = self::query()
            ->where('cms_website_id', $cmsWebsiteId)
            ->whereIn('slug', ['apply-online', 'admissions'])
            ->orderByDesc('updated_at')
            ->orderByRaw("FIELD(slug, 'apply-online', 'admissions')")
            ->first();

        if (! $canonical) {
            return null;
        }

        if ($canonical->slug === 'admissions') {
            $canonical->update(['slug' => 'apply-online']);

            return $canonical;
        }

        self::query()
            ->where('cms_website_id', $cmsWebsiteId)
            ->where('slug', 'admissions')
            ->where('id', '!=', $canonical->id)
            ->delete();

        return $canonical;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(CmsWebsite::class, 'cms_website_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CmsPageVersion::class, 'cms_page_id');
    }

    protected static function booted(): void
    {
        static::saving(function ($page) {
            if ($page->is_homepage) {
                static::where('cms_website_id', $page->cms_website_id)
                    ->where('id', '!=', $page->id)
                    ->update(['is_homepage' => false]);
            }

            if ($page->slug) {
                $page->slug = Str::slug($page->slug);
            }
        });

        static::saved(function (self $page): void {
            if ($page->wasRecentlyCreated || $page->wasChanged([
                'title', 'slug', 'is_homepage', 'is_published', 'hide_from_nav', 'sort_order', 'cms_website_id',
            ])) {
                CmsNavigationService::sync($page->website()->withoutGlobalScopes()->first() ?? $page->website);
            }
        });

        static::deleted(function (self $page): void {
            $website = CmsWebsite::withoutGlobalScopes()->find($page->cms_website_id);
            if ($website) {
                CmsNavigationService::sync($website);
            }
        });
    }

    public function getFullUrl(): string
    {
        $base = $this->website->school->domain ?? request()->getSchemeAndHttpHost();

        return $base.'/'.($this->is_homepage ? '' : $this->slug);
    }

    public function getEditUrl(): string
    {
        return VisualCmsBuilder::getUrl(['pageId' => $this->id]);
    }

    public function getPreviewUrl(): string
    {
        return route('cms.preview', ['slug' => $this->slug]);
    }
}
