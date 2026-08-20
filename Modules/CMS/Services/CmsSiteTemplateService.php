<?php

namespace Modules\CMS\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsSiteTemplate;
use Modules\CMS\Models\CmsWebsite;

/**
 * Manages named site templates (school1, school2...). Each template wraps a
 * shadow CmsWebsite holding the template's global theme, structure and
 * per-page design. The public live site is a materialized copy of whichever
 * template is currently active.
 */
class CmsSiteTemplateService
{
    /** Theme + branding columns copied verbatim between websites. */
    public const THEME_COLUMNS = [
        'active_template',
        'color_primary',
        'color_secondary',
        'color_accent',
        'color_background',
        'color_text',
        'color_card_bg',
        'color_border',
        'color_error',
        'color_success',
        'color_warning',
        'design_radius',
        'design_shadow',
        'design_container',
        'design_button_style',
        'design_spacing_scale',
        'font_primary',
        'font_secondary',
        'navigation_menu',
        'footer_menu',
        'social_links',
        'seo_title_suffix',
        'seo_global_description',
        'seo_og_image',
        'seo_default_meta',
        'logo_light_path',
        'logo_dark_path',
        'favicon_path',
        'apple_touch_icon_path',
        'announcement_banner',
        'custom_css',
        'custom_js',
        'custom_head',
    ];

    /** Standard starter pages seeded into a fresh template. */
    public const STARTER_PAGES = [
        ['title' => 'Home', 'slug' => 'home', 'is_homepage' => true],
        ['title' => 'About', 'slug' => 'about'],
        ['title' => 'Academics', 'slug' => 'academics'],
        ['title' => 'Admissions', 'slug' => 'apply-online'],
        ['title' => 'News & Events', 'slug' => 'news-events'],
        ['title' => 'Contact', 'slug' => 'contact'],
    ];

    public static function themeOf(CmsWebsite $website): array
    {
        return collect(self::THEME_COLUMNS)
            ->mapWithKeys(fn (string $column) => [$column => $website->getAttribute($column)])
            ->filter(fn ($value) => $value !== null)
            ->all();
    }

    /**
     * Create a named site template from a theme preset. Pages are seeded with
     * the live site's structure but fresh layout-1 starter blocks, so the
     * school can then pick any of the five layouts per page and customize.
     */
    public static function create(string $name, string $presetKey, ?string $description = null): CmsSiteTemplate
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $presets = CmsTemplateService::getTemplates();
        $presetKey = CmsTemplateService::canonicalTemplate($presetKey);
        $preset = $presets[$presetKey] ?? $presets['heritage-editorial'];

        $live = self::liveWebsite($schoolId);
        $sourceStructure = $live?->pages()->orderBy('sort_order')->get() ?? collect();

        $shadow = CmsWebsite::create(array_merge([
            'school_id' => $schoolId,
            'is_template_site' => true,
        ], self::presetTheme($presetKey, $preset)));

        $existing = $sourceStructure->isEmpty() ? collect(self::STARTER_PAGES) : $sourceStructure;

        foreach ($existing as $index => $structure) {
            $title = $structure['title'] ?? ($structure->title ?? 'Page');
            $slug = $structure['slug'] ?? ($structure->slug ?? Str::slug($title));
            $isHomepage = (bool) ($structure['is_homepage'] ?? ($structure->is_homepage ?? false));

            $blocks = collect(CmsTemplateService::pageLayoutsFor($slug, $isHomepage))
                ->first()['blocks']
                ?? [];
            $blocks = array_map(fn (string $type) => CmsTemplateService::starterBlock($type), $blocks);

            CmsPage::create([
                'school_id' => $schoolId,
                'cms_website_id' => $shadow->id,
                'title' => $title,
                'slug' => $slug,
                'is_homepage' => $isHomepage,
                'is_published' => true,
                'hide_from_nav' => (bool) ($structure['hide_from_nav'] ?? ($structure->hide_from_nav ?? false)),
                'sort_order' => $index,
                'blocks' => $blocks,
                'draft_blocks' => $blocks,
                'page_template' => $presetKey,
            ]);
        }

        CmsPage::consolidateAdmissionsAlias((int) $shadow->id);

        return CmsSiteTemplate::create([
            'school_id' => $schoolId,
            'name' => $name,
            'description' => $description,
            'cms_website_id' => $shadow->id,
        ]);
    }

    /** Build a template from the current live website, content included. */
    public static function importFromLive(string $name, ?string $description = null): CmsSiteTemplate
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $live = self::liveWebsite($schoolId);

        $shadow = CmsWebsite::create(array_merge([
            'school_id' => $schoolId,
            'is_template_site' => true,
        ], $live ? self::themeOf($live) : self::presetTheme('heritage-editorial', CmsTemplateService::getTemplates()['heritage-editorial'])));

        if ($live) {
            foreach ($live->pages()->orderBy('sort_order')->get() as $source) {
                $source->replicate(['cms_website_id', 'is_template_site'])->fill([
                    'cms_website_id' => $shadow->id,
                ])->save();
            }
        } else {
            foreach (self::STARTER_PAGES as $index => $structure) {
                $blocks = collect(CmsTemplateService::pageLayoutsFor($structure['slug'], $structure['is_homepage']))
                    ->first()['blocks']
                    ?? [];
                $blocks = array_map(fn (string $type) => CmsTemplateService::starterBlock($type), $blocks);

                CmsPage::create([
                    'school_id' => $schoolId,
                    'cms_website_id' => $shadow->id,
                    'title' => $structure['title'],
                    'slug' => $structure['slug'],
                    'is_homepage' => $structure['is_homepage'],
                    'is_published' => true,
                    'sort_order' => $index,
                    'blocks' => $blocks,
                    'draft_blocks' => $blocks,
                ]);
            }
        }

        CmsPage::consolidateAdmissionsAlias((int) $shadow->id);

        return CmsSiteTemplate::create([
            'school_id' => $schoolId,
            'name' => $name,
            'description' => $description,
            'cms_website_id' => $shadow->id,
        ]);
    }

    /** Copy a template (shadow website, its pages and the template row). */
    public static function duplicate(int $templateId): CmsSiteTemplate
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $template = CmsSiteTemplate::query()->where('school_id', $schoolId)->findOrFail($templateId);

        $shadow = $template->website;
        $newShadow = CmsWebsite::create(array_merge(self::themeOf($shadow), [
            'school_id' => $schoolId,
            'is_template_site' => true,
        ]));

        if ($shadow) {
            foreach ($shadow->pages()->orderBy('sort_order')->get() as $source) {
                $source->replicate(['cms_website_id'])->fill(['cms_website_id' => $newShadow->id])->save();
            }
        }

        CmsPage::consolidateAdmissionsAlias((int) $newShadow->id);

        $copy = CmsSiteTemplate::create([
            'school_id' => $schoolId,
            'name' => $template->name.' (Copy)',
            'description' => $template->description,
            'cms_website_id' => $newShadow->id,
        ]);

        return $copy;
    }

    public static function delete(int $templateId): bool
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $template = CmsSiteTemplate::query()->where('school_id', $schoolId)->findOrFail($templateId);

        if (self::isActive($template->id)) {
            throw new \RuntimeException('This template is currently live. Activate a different template before deleting it.');
        }

        if ($template->website) {
            $template->website->pages()->delete();
            $template->website->delete();
        }

        $template->delete();

        return true;
    }

    /**
     * Make a template live: copy its theme + pages (design and content) onto
     * the public website and record it as the active template.
     */
    public static function apply(int $templateId): CmsSiteTemplate
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $template = CmsSiteTemplate::query()->where('school_id', $schoolId)->findOrFail($templateId);
        $shadow = $template->website;

        if (! $shadow) {
            throw new \RuntimeException('This template has no website attached.');
        }

        $live = self::liveWebsite($schoolId);
        if (! $live) {
            $live = CmsWebsite::create(['school_id' => $schoolId, 'is_template_site' => false]);
        }

        $live->update(array_merge(self::themeOf($shadow), [
            'active_site_template_id' => $template->id,
            'is_template_site' => false,
        ]));

        // 'admissions' and 'apply-online' are aliases for one single page. If a
        // stale template carries the 'admissions' slug, normalize it first so the
        // template can never inject a duplicate admissions page onto the live site.
        if ($shadow->pages()->where('slug', 'apply-online')->exists()) {
            $shadow->pages()->where('slug', 'admissions')->delete();
        } elseif ($shadow->pages()->where('slug', 'admissions')->exists()) {
            $shadow->pages()->where('slug', 'admissions')->update(['slug' => 'apply-online']);
        }

        $shadowPages = $shadow->pages()->orderBy('sort_order')->get();
        $shadowSlugs = $shadowPages->pluck('slug');

        // The live site is an exact materialization of the active template:
        // clear homepages and drop pages that no longer exist in the template
        // so switching templates never leaves stale public pages behind.
        CmsPage::where('cms_website_id', $live->id)->update(['is_homepage' => false]);
        CmsPage::where('cms_website_id', $live->id)->whereNotIn('slug', $shadowSlugs)->delete();

        foreach ($shadowPages as $page) {
            $payload = [
                'title' => $page->title,
                'is_published' => (bool) $page->is_published,
                'is_homepage' => (bool) $page->is_homepage,
                'hide_from_nav' => (bool) $page->hide_from_nav,
                'sort_order' => (int) $page->sort_order,
                'page_template' => $page->page_template,
                'page_settings' => $page->page_settings ?? [],
                'blocks' => $page->blocks ?? [],
                'draft_blocks' => $page->draft_blocks ?? ($page->blocks ?? []),
            ];

            if (CmsPage::where('cms_website_id', $live->id)->where('slug', $page->slug)->exists()) {
                CmsPage::where('cms_website_id', $live->id)->where('slug', $page->slug)->update($payload);
            } else {
                CmsPage::create(array_merge(['school_id' => $schoolId, 'cms_website_id' => $live->id, 'slug' => $page->slug], $payload));
            }
        }

        CmsNavigationService::sync($live);
        CmsPage::consolidateAdmissionsAlias((int) $live->id);

        return $template;
    }

    /**
     * Create a named site template from one of the ten system catalogs. The
     * shadow website is built page-for-page from SiteTemplateCatalog so every
     * premade template ships its own distinct page compositions and components.
     */
    public static function createFromCatalog(string $templateKey, ?string $name = null, ?string $description = null): CmsSiteTemplate
    {
        $schoolId = app('current_tenant')->id ?? 0;
        $templateKey = CmsTemplateService::canonicalTemplate($templateKey);
        $presets = CmsTemplateService::getTemplates();
        $preset = $presets[$templateKey] ?? $presets['heritage-editorial'];
        $pages = SiteTemplateCatalog::pages($templateKey);

        $shadow = CmsWebsite::create(array_merge([
            'school_id' => $schoolId,
            'is_template_site' => true,
        ], self::presetTheme($templateKey, $preset)));

        $sort = 0;
        foreach ($pages as $slug => $page) {
            $blocks = array_map(
                fn (array $block) => ComponentRegistry::normalizeBlock($block),
                $page['blocks'] ?? []
            );

            CmsPage::create([
                'school_id' => $schoolId,
                'cms_website_id' => $shadow->id,
                'title' => $page['title'] ?? Str::title(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'is_homepage' => $slug === 'home',
                'is_published' => true,
                'hide_from_nav' => false,
                'sort_order' => $sort++,
                'blocks' => $blocks,
                'draft_blocks' => $blocks,
                'page_template' => $templateKey,
            ]);
        }

        CmsPage::consolidateAdmissionsAlias((int) $shadow->id);

        return CmsSiteTemplate::create([
            'school_id' => $schoolId,
            'name' => $name ?? $preset['name'],
            'description' => $description ?? ($preset['description'] ?? ''),
            'cms_website_id' => $shadow->id,
        ]);
    }

    /**
     * The ten system premade templates this school can choose from. Idempotent:
     * a template is only seeded when a shadow website for that preset does not
     * already exist for the tenant. Never mutates the shared catalogs.
     */
    public static function ensurePremadeTemplates(?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? app('current_tenant')->id ?? 0;
        $presets = CmsTemplateService::getTemplates();
        $seeded = [];

        foreach ($presets as $key => $preset) {
            $existing = CmsSiteTemplate::query()
                ->where('school_id', $schoolId)
                ->where('name', $preset['name'])
                ->first();

            if ($existing) {
                $seeded[$key] = $existing;

                continue;
            }

            $seeded[$key] = self::createFromCatalog($key, $preset['name'], $preset['description'] ?? '');
        }

        return $seeded;
    }

    /** Premade template list for the hub, keyed by system template key. */
    public static function premade(?int $schoolId = null): array
    {
        $schoolId = $schoolId ?? app('current_tenant')->id ?? 0;
        $presets = CmsTemplateService::getTemplates();
        $rows = CmsSiteTemplate::query()
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('name');

        $out = [];
        foreach ($presets as $key => $preset) {
            $template = $rows[$preset['name']] ?? null;
            $out[$key] = [
                'key' => $key,
                'name' => $preset['name'],
                'subtitle' => $preset['subtitle'] ?? '',
                'description' => $preset['description'] ?? '',
                'palette' => $preset['palette'] ?? [],
                'fonts' => $preset['fonts'] ?? [],
                'design' => $preset['design'] ?? [],
                'template_id' => $template?->id,
                'cms_website_id' => $template?->cms_website_id,
                'is_active' => $template && self::isActive($template->id),
            ];
        }

        return $out;
    }

    /** The user's own saved templates (excludes the ten premade presets). */
    public static function saved(?int $schoolId = null): Collection
    {
        $schoolId = $schoolId ?? app('current_tenant')->id ?? 0;
        $presetNames = collect(CmsTemplateService::getTemplates())->pluck('name')->all();

        return CmsSiteTemplate::query()
            ->where('school_id', $schoolId)
            ->whereNotIn('name', $presetNames)
            ->orderBy('id')
            ->get();
    }

    public static function liveWebsite(?int $schoolId = null): ?CmsWebsite
    {
        $schoolId = $schoolId ?? app('current_tenant')->id ?? null;
        if (! $schoolId) {
            return null;
        }

        return CmsWebsite::query()
            ->where('school_id', $schoolId)
            ->where('is_template_site', false)
            ->orderBy('id')
            ->first();
    }

    public static function active(): ?CmsSiteTemplate
    {
        $live = self::liveWebsite();

        return $live && $live->active_site_template_id
            ? CmsSiteTemplate::query()->find($live->active_site_template_id)
            : null;
    }

    public static function isActive(int $templateId): bool
    {
        $live = self::liveWebsite();

        return $live && (int) $live->active_site_template_id === $templateId;
    }

    private static function presetTheme(string $presetKey, array $preset): array
    {
        return [
            'active_template' => $presetKey,
            'color_primary' => $preset['palette']['primary'],
            'color_secondary' => $preset['palette']['secondary'],
            'color_accent' => $preset['palette']['accent'],
            'color_background' => $preset['palette']['background'],
            'color_text' => $preset['palette']['text'],
            'color_card_bg' => $preset['palette']['card_bg'],
            'font_primary' => $preset['fonts']['primary'],
            'font_secondary' => $preset['fonts']['secondary'],
            'design_radius' => $preset['design']['radius'],
            'design_shadow' => $preset['design']['shadow'],
            'design_container' => $preset['design']['container'],
            'design_button_style' => $preset['design']['button_style'],
        ];
    }
}
