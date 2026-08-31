<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\CMS\Models\CmsMedia;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsPageVersion;
use Modules\CMS\Models\CmsReusableBlock;
use Modules\CMS\Models\CmsSiteTemplate;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsNavigationService;
use Modules\CMS\Services\CmsSiteTemplateService;
use Modules\CMS\Services\CmsTemplateService;
use Modules\CMS\Services\SiteTemplateCatalog;

class VisualCmsBuilder extends Page
{
    use ModulePermissionAccess;
    use WithFileUploads;

    protected static string $view = 'livewire.cms.visual-cms-builder';

    protected static ?string $slug = 'cms/builder/{pageId}';

    protected static bool $shouldRegisterNavigation = false;

    public CmsWebsite $website;

    public CmsPage $page;

    /** Working draft array of page section blocks */
    public array $blocks = [];

    /** Status tracking */
    public bool $hasUnpublishedChanges = false;

    public string $activeTemplate = 'heritage-editorial';

    /** The template actually applied to the currently-loaded page (differs from
     *  $activeTemplate when a page has its own page_theme override).
     */
    public string $pageTemplate = 'heritage-editorial';

    /** Per-page layout ID (e.g. 'about_2') — stored in page_layout column. */
    public string $pageLayout = '';

    /** Whether the current page has its own theme override (vs site-wide). */
    public bool $pageHasThemeOverride = false;

    public string $editingMode = 'simple';

    public bool $showTemplateLibrary = false;

    public string $schoolTemplateName = '';

    /** School-owned saved page templates, backed by the existing reusable-block table. */
    public array $schoolTemplates = [];

    /** Source template for the "Mix & Match" aspect importer (Global Styles tab). */
    public string $aspectSourceTemplate = 'minimalist-academic';

    // Global Theme & Brand Tokens
    public string $color_primary = '#1e3a8a';

    public string $color_secondary = '#0284c7';

    public string $color_accent = '#f59e0b';

    public string $color_background = '#ffffff';

    public string $color_text = '#0f172a';

    public string $color_card_bg = '#f8fafc';

    public string $font_primary = 'Inter';

    public string $font_secondary = 'Outfit';

    /** Dedicated heading font for all section titles (falls back to font_secondary). */
    public string $font_heading = '';

    public string $design_radius = 'lg';

    public string $design_shadow = 'md';

    public string $design_container = 'wide';

    public string $design_button_style = 'pill';

    // Responsive Canvas Mode
    public string $previewSize = 'full'; // 'full', 'tablet', 'mobile'

    // Undo / Redo Stack
    public array $historyStack = [];

    public int $historyIndex = -1;

    // Selected Block Editing State
    public ?int $selectedBlockIndex = null;

    public array $selectedBlockData = [];

    public string $activeInspectorTab = 'content'; // 'content', 'typography', 'styles', 'media'

    public int $nudgeStep = 10;

    // Block Importer (deep per-block mixing across templates & layouts)
    public bool $showBlockImporter = false;

    public array $blockImportSources = [];

    public ?int $blockImportTargetIndex = null;

    // Track previous palette values so inherited block colors can follow theme changes.
    private string $prevBackground = '';

    private string $prevText = '';

    // SEO Settings Modal
    public bool $showSeoModal = false;

    public string $seoTitle = '';

    public string $seoDescription = '';

    public string $seoKeywords = '';

    // Image Upload State
    public ?TemporaryUploadedFile $tempImage = null;

    public string $mediaAltText = '';

    // Multi-Page Management
    public array $sitePages = [];

    public string $newPageTitle = '';

    public array $pageVersions = [];

    public ?int $editingPageId = null;

    public string $editingPageTitle = '';

    public string $editingPageSlug = '';

    public bool $editingPageHidden = false;

    // Website Templates (premade hub + saved bundles)
    public ?int $siteTemplateId = null;

    public array $premadeTemplates = [];

    public array $savedSiteTemplates = [];

    public bool $showNewSiteTemplate = false;

    public string $newSiteTemplateName = '';

    public string $newSiteTemplatePreset = 'heritage-editorial';

    // Real-Time Module Aggregates
    public array $stats = [];

    public array $news = [];

    public array $events = [];

    public array $staff = [];

    public function mount(int $pageId): void
    {
        $schoolId = app('current_tenant')->id ?? null;
        $this->page = CmsPage::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->findOrFail($pageId);
        $this->website = $this->page->website;

        // 'admissions' / 'apply-online' must be one single record so every edit
        // lands on the same page the public site renders. Delete any ghost and
        // redirect to the canonical record when the ghost was opened instead.
        if (in_array($this->page->slug, ['admissions', 'apply-online'], true)) {
            $canonical = CmsPage::consolidateAdmissionsAlias($this->page->cms_website_id);
            if ($canonical && $canonical->id !== $this->page->id) {
                $this->page = $canonical;
                $this->website = $canonical->website;
                $this->redirect(self::getUrl(['pageId' => $canonical->id]));
            }
        }

        // Load working draft blocks or published blocks
        $blocksData = $this->page->draft_blocks ?? $this->page->blocks ?? [];

        if (is_string($blocksData)) {
            $this->blocks = json_decode($blocksData, true) ?? [];
        } elseif (is_array($blocksData)) {
            $this->blocks = $blocksData;
        } else {
            $this->blocks = [];
        }

        $this->hasUnpublishedChanges = ($this->blocks !== ($this->page->blocks ?? []));

        $this->activeTemplate = CmsTemplateService::canonicalTemplate($this->website->active_template);
        $this->pageTemplate = CmsTemplateService::resolvePageTheme($this->page->page_theme, $this->page->page_template, $this->activeTemplate);
        $this->pageLayout = $this->page->page_layout ?? '';
        $this->pageHasThemeOverride = ! empty($this->page->page_theme);
        $this->editingMode = data_get($this->website->theme_overrides, 'editor_mode', 'simple');
        $this->color_primary = CmsTemplateService::safeHex($this->website->color_primary, '#1e3a8a');
        $this->color_secondary = CmsTemplateService::safeHex($this->website->color_secondary, '#0284c7');
        $this->color_accent = CmsTemplateService::safeHex($this->website->color_accent, '#f59e0b');
        $this->color_background = CmsTemplateService::safeHex($this->website->color_background, '#ffffff');
        $this->color_text = CmsTemplateService::safeHex($this->website->color_text, '#0f172a');
        $this->color_card_bg = CmsTemplateService::safeHex($this->website->color_card_bg, '#f8fafc');
        $this->font_primary = $this->website->font_primary ?: 'Inter';
        $this->font_secondary = $this->website->font_secondary ?: 'Outfit';
        $this->font_heading = $this->website->font_heading ?: $this->font_secondary;
        $this->design_radius = CmsTemplateService::safeToken($this->website->design_radius, CmsTemplateService::RADIUS_SCALE, 'lg');
        $this->design_shadow = CmsTemplateService::safeToken($this->website->design_shadow, CmsTemplateService::SHADOW_SCALE, 'md');
        $this->design_container = CmsTemplateService::safeToken($this->website->design_container, CmsTemplateService::CONTAINER_SCALE, 'wide');
        $this->design_button_style = CmsTemplateService::safeToken($this->website->design_button_style, CmsTemplateService::BUTTON_STYLES, 'pill');

        $this->seoTitle = $this->page->seo_title ?? $this->page->title;
        $this->seoDescription = $this->page->seo_description ?? '';
        $this->seoKeywords = $this->page->seo_keywords ?? '';

        $schoolId = app('current_tenant')->id ?? $this->page->school_id;

        $this->stats = [
            'students_count' => DB::table('students')->where('school_id', $schoolId)->count(),
            'courses_count' => DB::table('courses')->where('school_id', $schoolId)->count(),
            'books_count' => DB::table('inventory_items')->where('school_id', $schoolId)->count(),
            'teachers_count' => DB::table('users')->where('school_id', $schoolId)->whereIn('requested_role', ['teacher', 'staff'])->count(),
        ];

        $this->news = CmsTemplateService::resolveDynamicBlockData('news_feed', $schoolId);
        $this->events = CmsTemplateService::resolveDynamicBlockData('events_calendar', $schoolId);
        $this->staff = CmsTemplateService::resolveDynamicBlockData('staff_directory', $schoolId);

        $this->loadSitePages();
        $this->loadPageVersions();
        $this->loadSchoolTemplates();
        $this->loadSiteTemplates();
        $this->pushToHistory();
    }

    /** Load the Website Templates hub (premade presets + saved bundles). */
    private function loadSiteTemplates(): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        CmsSiteTemplateService::ensurePremadeTemplates($schoolId);

        $this->siteTemplateId = CmsSiteTemplate::query()
            ->where('school_id', $schoolId)
            ->where('cms_website_id', $this->website->id)
            ->value('id');

        $this->premadeTemplates = CmsSiteTemplateService::premade($schoolId);
        $this->savedSiteTemplates = CmsSiteTemplateService::saved($schoolId)
            ->map(fn (CmsSiteTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'thumbnail' => $t->thumbnail,
                'cms_website_id' => $t->cms_website_id,
                'is_active' => CmsSiteTemplateService::isActive($t->id),
            ])
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------------
    // Apply Changes & State Management
    // ---------------------------------------------------------------------

    public function applyCustomizations(): void
    {
        // If editing a block, flush selected block changes into main block list
        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
        }

        $this->pushToHistory();
        $this->saveWebsiteStyles();
        $this->syncPageToLive();

        $this->dispatch('notify', [
            'message' => '✨ Customization applied! Draft saved, preview updated and published to the live site.',
            'type' => 'success',
        ]);
    }

    /**
     * Ensures the currently edited blocks reach the LIVE website so changes
     * (including removed blocks) are reflected on the public site immediately.
     */
    private function syncPageToLive(): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')?->id ?? 0;
        $live = CmsSiteTemplateService::liveWebsite($schoolId);

        if (! $live) {
            return;
        }

        $blocks = $this->blocks;

        // Editing the live website directly: the block list IS the published state.
        if ((int) $live->id === (int) $this->page->cms_website_id) {
            $this->page->update([
                'blocks' => $blocks,
                'draft_blocks' => $blocks,
                'is_published' => true,
            ]);
            $this->hasUnpublishedChanges = false;

            // Keep 'admissions' / 'apply-online' as one record on the live site.
            if (in_array($this->page->slug, ['admissions', 'apply-online'], true)) {
                CmsPage::consolidateAdmissionsAlias((int) $this->page->cms_website_id);
            }

            return;
        }

        // Editing a template/shadow website: materialize this page onto the live site.
        // Admissions has two public URLs, but only one persisted page.  Resolve
        // the target with the same rule used by the renderer; otherwise a stale
        // `admissions` row can be updated while `/apply-online` keeps rendering
        // the unchanged `apply-online` row.
        $isAdmissionsPage = in_array($this->page->slug, ['admissions', 'apply-online'], true);
        $targetSlug = $isAdmissionsPage ? 'apply-online' : $this->page->slug;
        $livePage = $isAdmissionsPage
            ? CmsPage::consolidateAdmissionsAlias((int) $live->id)
            : CmsPage::query()
                ->where('cms_website_id', $live->id)
                ->where('slug', $targetSlug)
                ->first();

        $payload = [
            'school_id' => $schoolId,
            'title' => $this->page->title,
            'is_published' => true,
            'is_homepage' => (bool) $this->page->is_homepage,
            'hide_from_nav' => (bool) $this->page->hide_from_nav,
            'sort_order' => (int) $this->page->sort_order,
            'page_template' => $this->page->page_template,
            'page_layout' => $this->page->page_layout,
            'page_theme' => $this->page->page_theme,
            'page_settings' => $this->page->page_settings ?? [],
            'blocks' => $blocks,
            'draft_blocks' => $blocks,
        ];

        if ($livePage) {
            $livePage->update($payload);
        } else {
            CmsPage::create(array_merge(['cms_website_id' => $live->id, 'slug' => $targetSlug], $payload));
        }

        if ($isAdmissionsPage) {
            CmsPage::consolidateAdmissionsAlias((int) $live->id);
        }

        CmsNavigationService::sync($live);
    }

    private function pushToHistory(): void
    {
        if ($this->historyIndex < count($this->historyStack) - 1) {
            $this->historyStack = array_slice($this->historyStack, 0, $this->historyIndex + 1);
        }

        $this->historyStack[] = $this->blocks;
        $this->historyIndex = count($this->historyStack) - 1;

        if (count($this->historyStack) > 30) {
            array_shift($this->historyStack);
            $this->historyIndex--;
        }

        $this->syncDraft();
    }

    /** Keep the draft canvas current without creating an undo entry for every keystroke. */
    public function updatedSelectedBlockData(): void
    {
        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
            $this->syncDraft();
        }
    }

    public function updated(string $name): void
    {
        if (in_array($name, [
            'color_primary', 'color_secondary', 'color_accent', 'color_background', 'color_text',
            'color_card_bg', 'font_primary', 'font_secondary', 'design_radius', 'design_shadow',
            'design_container', 'design_button_style',
        ], true)) {
            $this->hasUnpublishedChanges = true;
        }

        // Let blocks that were still inheriting the old site background / text color
        // follow the new palette, so site-wide theme changes reflect instantly.
        if ($name === 'color_background' || $name === 'color_text') {
            $this->refreshInheritedBlockColors($this->prevBackground, $this->prevText);
        }
    }

    public function updatingColorBackground(): void
    {
        $this->prevBackground = $this->color_background;
    }

    public function updatingColorText(): void
    {
        $this->prevText = $this->color_text;
    }

    /** Blocks that still matched the previous default palette become "inherit" (''), following the site theme. */
    private function refreshInheritedBlockColors(string $previousBackground, string $previousText): void
    {
        if ($previousBackground === '') {
            $previousBackground = $this->color_background;
        }
        if ($previousText === '') {
            $previousText = $this->color_text;
        }

        foreach ($this->blocks as $i => $block) {
            $styles = $block['styles'] ?? [];
            if (($styles['bg_color'] ?? null) === $previousBackground) {
                $styles['bg_color'] = '';
            }
            if (($styles['text_color'] ?? null) === $previousText) {
                $styles['text_color'] = '';
            }
            if ($styles !== ($block['styles'] ?? [])) {
                $this->blocks[$i]['styles'] = $styles;
            }
        }

        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
        }
    }

    private function syncDraft(): void
    {
        // Draft-first workflow: edits are visible in the builder canvas and
        // the full-page preview, but the public site only changes when the
        // user clicks "Publish" (publishPage()).
        $this->page->update(['draft_blocks' => $this->blocks]);
        $this->hasUnpublishedChanges = ($this->blocks !== ($this->page->blocks ?? []));
    }

    public function undo(): void
    {
        if ($this->historyIndex > 0) {
            $this->historyIndex--;
            $this->blocks = $this->historyStack[$this->historyIndex];
            $this->syncDraft();
            $this->selectedBlockIndex = null;
            $this->dispatch('notify', ['message' => 'Action undone.', 'type' => 'info']);
        }
    }

    public function redo(): void
    {
        if ($this->historyIndex < count($this->historyStack) - 1) {
            $this->historyIndex++;
            $this->blocks = $this->historyStack[$this->historyIndex];
            $this->syncDraft();
            $this->selectedBlockIndex = null;
            $this->dispatch('notify', ['message' => 'Action redone.', 'type' => 'info']);
        }
    }

    // ---------------------------------------------------------------------
    // Template Switching (Preserves Content)
    // ---------------------------------------------------------------------

    public function setActiveTemplate(string $val): void
    {
        $val = CmsTemplateService::canonicalTemplate($val);
        if (! in_array($val, array_keys(CmsTemplateService::getTemplates()))) {
            return;
        }

        $templates = CmsTemplateService::getTemplates();
        $preset = $templates[$val];
        $previousTemplate = CmsTemplateService::canonicalTemplate($this->website->active_template ?? 'heritage-editorial');
        $previousBackground = CmsTemplateService::safeHex($this->color_background, '#ffffff');
        $previousText = CmsTemplateService::safeHex($this->color_text, '#0f172a');

        $this->activeTemplate = $val;
        $this->color_primary = $preset['palette']['primary'];
        $this->color_secondary = $preset['palette']['secondary'];
        $this->color_accent = $preset['palette']['accent'];
        $this->color_background = $preset['palette']['background'];
        $this->color_text = $preset['palette']['text'];
        $this->color_card_bg = $preset['palette']['card_bg'];
        $this->font_primary = $preset['fonts']['primary'];
        $this->font_secondary = $preset['fonts']['secondary'];
        $this->font_heading = $preset['fonts']['heading'] ?? $preset['fonts']['primary'];
        $this->design_radius = $preset['design']['radius'];
        $this->design_shadow = $preset['design']['shadow'];
        $this->design_container = $preset['design']['container'];
        $this->design_button_style = $preset['design']['button_style'];

        // Refresh only inherited section tokens. Explicit per-section choices stay intact.
        $this->blocks = array_map(function (array $block) use ($previousBackground, $previousText) {
            $styles = $block['styles'] ?? [];
            if (($styles['bg_color'] ?? null) === $previousBackground) {
                $styles['bg_color'] = '';
            }
            if (($styles['text_color'] ?? null) === $previousText) {
                $styles['text_color'] = '';
            }
            $block['styles'] = $styles;

            return $block;
        }, $this->blocks);

        $this->saveWebsiteStyles();
        $this->scaffoldTemplatePages($val);
        $this->pushToHistory();

        // A site-wide template switch must actually change what the public site
        // and the studio preview render. Pages still pinned to the previous
        // site-wide template follow the switch; pages with an explicit per-page
        // theme override keep their own template.
        CmsPage::where('cms_website_id', $this->website->id)
            ->where('page_theme', $previousTemplate)
            ->update(['page_theme' => $val]);
        $this->page = $this->page->fresh() ?? $this->page;
        $this->pageTemplate = CmsTemplateService::resolvePageTheme($this->page->page_theme, $this->page->page_template, $val);
        $this->loadSitePages();

        $this->dispatch('notify', ['message' => "Template switched to '{$preset['name']}' – all content preserved!", 'type' => 'success']);
    }

    /** Apply a different theme to just the current page (per-page override). */
    public function switchPageTemplate(string $template): void
    {
        $template = CmsTemplateService::canonicalTemplate($template);
        $templates = CmsTemplateService::getTemplates();
        if (! isset($templates[$template])) {
            return;
        }

        $this->pageTemplate = $template;
        $this->pageHasThemeOverride = true;
        $this->page->update(['page_theme' => $template]);

        $this->dispatch('notify', ['message' => "Page theme set to '{$templates[$template]['name']}'.", 'type' => 'success']);
    }

    /** Remove per-page theme override — page falls back to site-wide theme. */
    public function resetPageTheme(): void
    {
        $this->pageTemplate = $this->activeTemplate;
        $this->pageHasThemeOverride = false;
        $this->page->update(['page_theme' => null]);

        $templates = CmsTemplateService::getTemplates();
        $name = $templates[$this->activeTemplate]['name'] ?? $this->activeTemplate;
        $this->dispatch('notify', ['message' => "Page now uses the site-wide theme ({$name}).", 'type' => 'success']);
    }

    /**
     * "Mix & Match" importer: copy a single design aspect from another template
     * into this website without switching templates or touching page content.
     * Supported aspects: palette, fonts, design (tokens), all.
     */
    public function importTemplateAspect(string $sourceTemplate, string $aspect): void
    {
        $templates = CmsTemplateService::getTemplates();
        if (! isset($templates[$sourceTemplate])) {
            return;
        }
        if (! in_array($aspect, ['palette', 'fonts', 'design', 'all'], true)) {
            $aspect = 'all';
        }

        $preset = $templates[$sourceTemplate];
        $previousBackground = CmsTemplateService::safeHex($this->color_background, '#ffffff');
        $previousText = CmsTemplateService::safeHex($this->color_text, '#0f172a');

        if (in_array($aspect, ['palette', 'all'], true)) {
            $this->color_primary = $preset['palette']['primary'];
            $this->color_secondary = $preset['palette']['secondary'];
            $this->color_accent = $preset['palette']['accent'];
            $this->color_background = $preset['palette']['background'];
            $this->color_text = $preset['palette']['text'];
            $this->color_card_bg = $preset['palette']['card_bg'];
        }

        if (in_array($aspect, ['fonts', 'all'], true)) {
            $this->font_primary = $preset['fonts']['primary'];
            $this->font_secondary = $preset['fonts']['secondary'];
        }

        if (in_array($aspect, ['design', 'all'], true)) {
            $this->design_radius = $preset['design']['radius'];
            $this->design_shadow = $preset['design']['shadow'];
            $this->design_container = $preset['design']['container'];
            $this->design_button_style = $preset['design']['button_style'];
        }

        // Refresh only inherited section tokens. Explicit per-section choices stay intact.
        if (in_array($aspect, ['palette', 'all'], true)) {
            $this->blocks = array_map(function (array $block) use ($previousBackground, $previousText, $preset) {
                $styles = $block['styles'] ?? [];
                if (($styles['bg_color'] ?? null) === $previousBackground) {
                    $styles['bg_color'] = $preset['palette']['background'];
                }
                if (($styles['text_color'] ?? null) === $previousText) {
                    $styles['text_color'] = $preset['palette']['text'];
                }
                $block['styles'] = $styles;

                return $block;
            }, $this->blocks);
        }

        $this->saveWebsiteStyles();
        $this->pushToHistory();

        $labels = [
            'palette' => 'Color palette',
            'fonts' => 'Font pairing',
            'design' => 'Design tokens',
            'all' => 'Full theme',
        ];

        $this->dispatch('notify', [
            'message' => "Imported {$labels[$aspect]} from '{$preset['name']}' into this website.",
            'type' => 'success',
        ]);
    }

    /**
     * Add the template's recommended pages only when a website is still empty.
     * Existing pages are deliberately never overwritten: switching a theme is visual,
     * not destructive to a school's carefully authored content.
     */
    private function scaffoldTemplatePages(string $template): void
    {
        if (CmsPage::where('cms_website_id', $this->website->id)->count() > 1) {
            return;
        }

        // Seed the full six-page site from the new template's catalog so a
        // fresh site becomes a complete, ready-to-edit website immediately.
        $template = CmsTemplateService::canonicalTemplate($template);
        $sort = 0;

        foreach (SiteTemplateCatalog::pages($template) as $slug => $sp) {
            $sort++;
            if (CmsPage::where('cms_website_id', $this->website->id)->where('slug', $slug)->exists()) {
                continue;
            }

            CmsPage::create([
                'school_id' => $this->page->school_id,
                'cms_website_id' => $this->website->id,
                'title' => $sp['title'],
                'slug' => $slug,
                'is_homepage' => $slug === 'home',
                'blocks' => $sp['blocks'],
                'draft_blocks' => $sp['blocks'],
                'is_published' => true,
                'sort_order' => $sort,
                'page_template' => $template,
                'page_theme' => $template,
            ]);
        }

        $this->loadSitePages();
    }

    public function saveWebsiteStyles(): void
    {
        $this->website->update([
            'active_template' => $this->activeTemplate,
            'color_primary' => CmsTemplateService::safeHex($this->color_primary),
            'color_secondary' => CmsTemplateService::safeHex($this->color_secondary),
            'color_accent' => CmsTemplateService::safeHex($this->color_accent),
            'color_background' => CmsTemplateService::safeHex($this->color_background, '#ffffff'),
            'color_text' => CmsTemplateService::safeHex($this->color_text, '#0f172a'),
            'color_card_bg' => CmsTemplateService::safeHex($this->color_card_bg, '#f8fafc'),
            'font_primary' => $this->font_primary,
            'font_secondary' => $this->font_secondary,
            'font_heading' => $this->font_heading !== '' ? $this->font_heading : null,
            'design_radius' => CmsTemplateService::safeToken($this->design_radius, CmsTemplateService::RADIUS_SCALE, 'lg'),
            'design_shadow' => CmsTemplateService::safeToken($this->design_shadow, CmsTemplateService::SHADOW_SCALE, 'md'),
            'design_container' => CmsTemplateService::safeToken($this->design_container, CmsTemplateService::CONTAINER_SCALE, 'wide'),
            'design_button_style' => CmsTemplateService::safeToken($this->design_button_style, CmsTemplateService::BUTTON_STYLES, 'pill'),
        ]);

        CmsNavigationService::sync($this->website);
    }

    /** Keep the public header derived from the pages people manage in Site Navigator. */
    private function syncNavigationMenu(): void
    {
        CmsNavigationService::sync($this->website);
    }

    // ---------------------------------------------------------------------
    // Block Selection, Customization & CRUD
    // ---------------------------------------------------------------------

    public function selectBlock(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            $this->selectedBlockIndex = null;
            $this->selectedBlockData = [];

            return;
        }

        $this->selectedBlockIndex = $index;
        $this->selectedBlockData = $this->blocks[$index];

        $this->selectedBlockData['styles'] = array_merge([
            'bg_style' => 'solid',
            'bg_color' => '',
            'bg_gradient_end' => '#f1f5f9',
            'text_color' => '',
            'title_color' => '',
            'padding_top' => 'py-16',
            'padding_bottom' => 'py-16',
            'font_family' => '',
            'title_font' => '',
            'font_size' => 16,
            'title_size' => 36,
            'line_height' => '',
            'text_align' => 'text-center',
            'container' => 'default',
            'animate' => 'fade-up',
            'bg_image_opacity' => 1,
            'offset_x' => 0,
            'offset_y' => 0,
            'hidden' => false,
        ], $this->selectedBlockData['styles'] ?? []);
        $this->activeInspectorTab = 'content';
        $this->dispatch('cms-inspector-open');
    }

    public function updateBlockSettings(): void
    {
        if (! is_null($this->selectedBlockIndex)) {
            $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
            $this->pushToHistory();
            $this->dispatch('notify', ['message' => 'Section details saved.', 'type' => 'success']);
        }
    }

    public function toggleBlockVisibility(int $index): void
    {
        $current = $this->blocks[$index]['styles']['hidden'] ?? false;
        $this->blocks[$index]['styles']['hidden'] = ! $current;
        $this->pushToHistory();
    }

    public function duplicateBlock(int $index): void
    {
        $copy = $this->blocks[$index];
        $copy['id'] = uniqid('blk_');
        array_splice($this->blocks, $index + 1, 0, [$copy]);
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => 'Section duplicated.', 'type' => 'success']);
    }

    public function reorderBlocks(array $orderedIds): void
    {
        $byId = collect($this->blocks)->keyBy('id');
        $reordered = [];
        foreach ($orderedIds as $id) {
            if ($byId->has($id)) {
                $reordered[] = $byId->get($id);
            }
        }
        foreach ($this->blocks as $b) {
            if (! in_array($b['id'], $orderedIds, true)) {
                $reordered[] = $b;
            }
        }
        $this->blocks = $reordered;
        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];
        $this->pushToHistory();
    }

    public function addBlock(string $type): void
    {
        $this->blocks[] = CmsTemplateService::starterBlock($type);
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => 'New section added to page.', 'type' => 'success']);
    }

    // ---------------------------------------------------------------------
    // Block Importer — deep per-block mixing across the 5 predesigned
    // layouts and the school's other saved site templates.
    // ---------------------------------------------------------------------

    public function openBlockImporter(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $this->blockImportTargetIndex = $index;
        $this->blockImportSources = [];
        $this->showBlockImporter = true;

        $kind = $this->blockKind($this->page->slug, (bool) $this->page->is_homepage);
        $layouts = CmsTemplateService::pageLayoutsFor($this->page->slug, (bool) $this->page->is_homepage);

        // 1. The 5 predesigned layouts for this page kind.
        foreach ($layouts as $handle => $layout) {
            $this->blockImportSources[] = [
                'key' => 'layout:'.$handle,
                'title' => $layout['name'],
                'badge' => 'Predesigned',
                'blocks' => collect($layout['blocks'])
                    ->map(fn (string $type) => [
                        'id' => 'starter-'.$type.'-'.uniqid(),
                        'type' => $type,
                        'title' => CmsTemplateService::starterBlock($type)['title'] ?? $type,
                        'preview' => ucfirst(str_replace('_', ' ', $type)),
                        'data' => CmsTemplateService::starterBlock($type),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        // 2. The five system templates from SiteTemplateCatalog (same page
        //    kind) — borrow fully-styled components across any template.
        $catalogPageSlug = match ($kind) {
            'home' => 'home',
            'admission' => 'apply-online',
            'news' => 'news-events',
            'contact' => 'contact',
            default => 'about',
        };
        $templateNames = collect(CmsTemplateService::getTemplates())
            ->map(fn (array $t) => $t['name'])
            ->all();

        foreach (SiteTemplateCatalog::catalog() as $catalogKey => $catalogPages) {
            $catalogPage = $catalogPages[$catalogPageSlug] ?? $catalogPages[$this->page->slug] ?? null;
            if (! $catalogPage) {
                continue;
            }

            $pageBlocks = collect($catalogPage['blocks'])->values();
            if ($pageBlocks->isEmpty()) {
                continue;
            }

            $this->blockImportSources[] = [
                'key' => 'catalog:'.$catalogKey.':'.$catalogPageSlug,
                'title' => ($templateNames[$catalogKey] ?? ucfirst($catalogKey)).' — '.($catalogPage['title'] ?? $catalogPageSlug),
                'badge' => 'System template',
                'blocks' => $pageBlocks->map(fn (array $block) => [
                    'id' => $block['id'] ?? uniqid('blk_'),
                    'type' => $block['type'] ?? 'section',
                    'title' => $block['title'] ?? ucfirst(str_replace('_', ' ', $block['type'] ?? 'section')),
                    'preview' => ucfirst(str_replace('_', ' ', $block['type'] ?? 'section')),
                    'data' => $block,
                ])->all(),
            ];
        }

        // 3. The school's other saved site templates (same page kind).
        $schoolId = $this->page->school_id;
        $otherTemplates = CmsSiteTemplate::query()
            ->where('school_id', $schoolId)
            ->with('website.pages')
            ->get()
            ->filter(fn ($template) => $template->website && $template->website->id !== $this->website->id);

        foreach ($otherTemplates as $template) {
            $pages = $template->website->pages
                ->filter(fn ($page) => $this->blockKind($page->slug, (bool) $page->is_homepage) === $kind);

            if ($pages->isEmpty()) {
                continue;
            }

            foreach ($pages as $page) {
                $pageBlocks = collect($page->draft_blocks ?? $page->blocks ?? [])->values();
                if ($pageBlocks->isEmpty()) {
                    continue;
                }

                $this->blockImportSources[] = [
                    'key' => 'tpl:'.$template->id.':'.$page->slug,
                    'title' => $template->name.' — '.$page->title,
                    'badge' => 'Your template',
                    'blocks' => $pageBlocks->map(fn (array $block) => [
                        'id' => $block['id'] ?? uniqid('blk_'),
                        'type' => $block['type'] ?? 'section',
                        'title' => $block['title'] ?? ucfirst(str_replace('_', ' ', $block['type'] ?? 'section')),
                        'preview' => ucfirst(str_replace('_', ' ', $block['type'] ?? 'section')),
                        'data' => $block,
                    ])->all(),
                ];
            }
        }
    }

    public function closeBlockImporter(): void
    {
        $this->showBlockImporter = false;
        $this->blockImportTargetIndex = null;
        $this->blockImportSources = [];
    }

    /**
     * Replace a block entirely (structure, styles and starter content) or copy
     * only its styles onto the selected block.
     */
    public function importBlock(string $mode, string $sourceKey, string $sourceBlockId): void
    {
        if (is_null($this->blockImportTargetIndex) || ! isset($this->blocks[$this->blockImportTargetIndex])) {
            return;
        }
        if (! in_array($mode, ['replace', 'styles'], true)) {
            return;
        }

        $targetIndex = $this->blockImportTargetIndex;
        $target = $this->blocks[$targetIndex];
        $source = null;

        foreach ($this->blockImportSources as $group) {
            if ($group['key'] !== $sourceKey) {
                continue;
            }
            $source = collect($group['blocks'])->first(fn (array $b) => $b['id'] === $sourceBlockId);
            break;
        }

        if (! $source) {
            return;
        }

        $sourceBlock = $source['data'];

        if ($mode === 'replace') {
            // Keep the current block's stable id so content auto-publishing and
            // design re-applies keep matching the same block.
            $replacement = $sourceBlock;
            $replacement['id'] = $target['id'];
            $this->blocks[$targetIndex] = $replacement;
        } else {
            if (($sourceBlock['type'] ?? null) !== ($target['type'] ?? null)) {
                $this->dispatch('notify', [
                    'message' => 'Copy styles works between sections of the same type only.',
                    'type' => 'warning',
                ]);

                return;
            }
            $this->blocks[$targetIndex]['styles'] = $sourceBlock['styles'] ?? [];
        }

        $this->selectedBlockIndex = $targetIndex;
        $this->selectedBlockData = $this->blocks[$targetIndex];
        $this->showBlockImporter = false;
        $this->blockImportTargetIndex = null;
        $this->blockImportSources = [];

        $this->syncDraft();
        $this->pushToHistory();

        $this->dispatch('notify', [
            'message' => $mode === 'replace'
                ? 'Block replaced from '.$source['title'].'.'
                : 'Styles copied from '.$source['title'].'.',
            'type' => 'success',
        ]);
    }

    private function blockKind(string $slug, bool $isHomepage): string
    {
        if ($isHomepage || in_array($slug, ['home', 'index'], true)) {
            return 'home';
        }
        if (str_contains($slug, 'admission') || str_contains($slug, 'apply')) {
            return 'admission';
        }
        if (str_contains($slug, 'news')) {
            return 'news';
        }
        if (str_contains($slug, 'contact')) {
            return 'contact';
        }

        return 'about';
    }

    /**
     * Five ready-to-use layouts are available for every key page kind. They are
     * deliberately block-based, so every word, image, and position remains editable.
     *
     * Layout choice is stored per page (page_layout = "about_2" etc.) and the
     * starter blocks are saved to the DRAFT so they show in preview; publishing
     * pushes them live. The theme (template) is unaffected — layout ≠ template.
     */
    public function applyPageTemplate(string $layout): void
    {
        $layouts = CmsTemplateService::pageLayoutsFor($this->page->slug, $this->page->is_homepage);
        if (! isset($layouts[$layout])) {
            return;
        }

        $this->blocks = [];
        foreach ($layouts[$layout]['blocks'] as $type) {
            $this->blocks[] = CmsTemplateService::starterBlock($type);
        }

        $this->page->update(['page_layout' => $layout]);
        $this->syncDraft();
        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => "{$layouts[$layout]['name']} applied. Replace the starter text and images to make it yours.", 'type' => 'success']);
    }

    /** Clear the per-page layout — page keeps its current blocks. */
    public function resetPageLayout(): void
    {
        $this->pageLayout = '';
        $this->page->update(['page_layout' => null]);
        $this->dispatch('notify', ['message' => 'Page layout cleared.', 'type' => 'success']);
    }

    public function moveBlockUp(int $index): void
    {
        if ($index > 0) {
            [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
            $this->selectedBlockIndex = null;
            $this->selectedBlockData = [];
            $this->pushToHistory();
        }
    }

    public function moveBlockDown(int $index): void
    {
        if ($index < count($this->blocks) - 1) {
            [$this->blocks[$index + 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index + 1]];
            $this->selectedBlockIndex = null;
            $this->selectedBlockData = [];
            $this->pushToHistory();
        }
    }

    /** Nudge the selected block a number of units from its current position. */
    public function nudgeBlock(string $direction): void
    {
        if (is_null($this->selectedBlockIndex) || ! isset($this->blocks[$this->selectedBlockIndex])) {
            return;
        }

        $step = max(1, (int) $this->nudgeStep);
        $styles = $this->blocks[$this->selectedBlockIndex]['styles'] ?? [];
        $x = (int) ($styles['offset_x'] ?? 0);
        $y = (int) ($styles['offset_y'] ?? 0);

        match ($direction) {
            'up' => $y -= $step,
            'down' => $y += $step,
            'left' => $x -= $step,
            'right' => $x += $step,
            default => null,
        };

        $styles['offset_x'] = $x;
        $styles['offset_y'] = $y;
        $this->blocks[$this->selectedBlockIndex]['styles'] = $styles;
        $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
        $this->syncDraft();
    }

    /** Reset the selected block's nudged position back to zero. */
    public function resetBlockOffset(): void
    {
        if (is_null($this->selectedBlockIndex) || ! isset($this->blocks[$this->selectedBlockIndex])) {
            return;
        }
        $styles = $this->blocks[$this->selectedBlockIndex]['styles'] ?? [];
        $styles['offset_x'] = 0;
        $styles['offset_y'] = 0;
        $this->blocks[$this->selectedBlockIndex]['styles'] = $styles;
        $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
        $this->syncDraft();
    }

    /** Reset a group of the selected block's styles back to "inherit site theme". */
    public function resetBlockStyle(string $group): void
    {
        if (is_null($this->selectedBlockIndex) || ! isset($this->blocks[$this->selectedBlockIndex])) {
            return;
        }
        $styles = $this->blocks[$this->selectedBlockIndex]['styles'] ?? [];

        if ($group === 'typography') {
            $styles = array_merge($styles, [
                'title_font' => '', 'font_family' => '', 'title_color' => '', 'text_color' => '',
                'title_size' => 36, 'font_size' => 16, 'line_height' => '', 'text_align' => 'text-center',
            ]);
        } elseif ($group === 'background') {
            $styles = array_merge($styles, [
                'bg_style' => 'solid', 'bg_color' => '', 'bg_gradient_end' => '#f1f5f9',
                'bg_image_url' => '', 'bg_image_opacity' => 1,
            ]);
        } elseif ($group === 'photo') {
            $styles = array_merge($styles, [
                'image_fit' => '', 'image_position' => '', 'image_ratio' => '',
                'image_width' => '', 'image_radius' => '',
                'gallery_fit' => '', 'gallery_position' => '', 'gallery_ratio' => '', 'gallery_radius' => '',
                'card_image_fit' => '', 'card_image_position' => '', 'card_image_ratio' => '', 'card_image_radius' => '',
            ]);
        } elseif ($group === 'carousel') {
            $this->blocks[$this->selectedBlockIndex] = array_merge($this->blocks[$this->selectedBlockIndex], [
                'card_width' => 380, 'card_height' => 300,
                'radius' => 12, 'tilt' => 12, 'side_tilt' => 8, 'gap' => 8,
                'opacity' => 60, 'autoplay' => false,
                'title_position' => 'bottomLeft',
            ]);
            $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
            $this->syncDraft();
            $this->dispatch('notify', ['message' => 'Carousel photo settings reset to template default.', 'type' => 'info']);

            return;
        } elseif ($group === 'orbit') {
            $this->blocks[$this->selectedBlockIndex] = array_merge($this->blocks[$this->selectedBlockIndex], [
                'center_label' => 'Pillars', 'item_size' => 84,
                'orbit_radius_x' => 180, 'orbit_radius_y' => 70,
                'rotation_speed' => 6, 'direction' => 'clockwise', 'tilt' => 18, 'variant' => 'ellipse',
            ]);
            $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
            $this->syncDraft();
            $this->dispatch('notify', ['message' => 'Orbit diagram settings reset to template default.', 'type' => 'info']);

            return;
        } else {
            return;
        }

        $this->blocks[$this->selectedBlockIndex]['styles'] = $styles;
        $this->selectedBlockData = $this->blocks[$this->selectedBlockIndex];
        $this->syncDraft();
        $this->dispatch('notify', ['message' => 'Block style reset to site defaults.', 'type' => 'info']);
    }

    public function deleteBlock(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            $this->selectedBlockIndex = null;
            $this->selectedBlockData = [];

            return;
        }

        array_splice($this->blocks, $index, 1);
        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];
        $this->pushToHistory();
        $this->syncPageToLive();
        $this->dispatch('notify', ['message' => 'Section removed and removed from the live site.', 'type' => 'info']);
    }

    public function deleteSelectedBlock(): void
    {
        if (! is_null($this->selectedBlockIndex)) {
            $this->deleteBlock($this->selectedBlockIndex);
        }
    }

    // ---------------------------------------------------------------------
    // Image Uploads Fix
    // ---------------------------------------------------------------------

    public function attachUploadedImage(string $targetField): void
    {
        $this->validate(['tempImage' => 'required|image|max:8192']);

        if (! $this->tempImage) {
            return;
        }

        $schoolId = app('current_tenant')->id ?? $this->page->school_id;
        $path = $this->tempImage->store("cms-media/{$schoolId}/images", 'public');
        $url = asset('storage/'.$path);

        $dimensions = @getimagesize($this->tempImage->getRealPath()) ?: [null, null];

        CmsMedia::create([
            'school_id' => $schoolId,
            'uuid' => (string) Str::uuid(),
            'filename' => basename($path),
            'original_filename' => $this->tempImage->getClientOriginalName(),
            'mime_type' => $this->tempImage->getMimeType(),
            'extension' => $this->tempImage->getClientOriginalExtension(),
            'file_size' => $this->tempImage->getSize(),
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'alt_text' => $this->mediaAltText,
            'folder' => 'images',
        ]);

        data_set($this->selectedBlockData, $targetField, $url);

        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
        }

        $this->tempImage = null;
        $this->mediaAltText = '';
        $this->pushToHistory();

        $this->dispatch('notify', ['message' => '📷 Image uploaded and applied successfully!', 'type' => 'success']);
    }

    public function uploadWebsiteLogo(): void
    {
        $this->validate(['tempImage' => 'required|image|max:4096']);
        $schoolId = $this->page->school_id;
        $path = $this->tempImage->store("cms-media/{$schoolId}/branding", 'public');
        $dimensions = @getimagesize($this->tempImage->getRealPath()) ?: [null, null];

        CmsMedia::create([
            'school_id' => $schoolId, 'uuid' => (string) Str::uuid(), 'filename' => basename($path),
            'original_filename' => $this->tempImage->getClientOriginalName(), 'mime_type' => $this->tempImage->getMimeType(),
            'extension' => $this->tempImage->getClientOriginalExtension(), 'file_size' => $this->tempImage->getSize(),
            'disk' => 'public', 'path' => $path, 'url' => asset('storage/'.$path),
            'width' => $dimensions[0], 'height' => $dimensions[1],
            'alt_text' => $this->mediaAltText ?: $this->website->school->name.' logo', 'folder' => 'branding',
        ]);

        $this->website->update(['logo_light_path' => $path, 'logo_dark_path' => $path]);
        $this->tempImage = null;
        $this->mediaAltText = '';
        $this->dispatch('notify', ['message' => 'School logo updated.', 'type' => 'success']);
    }

    // ---------------------------------------------------------------------
    // Multi-Page Site Navigation
    // ---------------------------------------------------------------------

    public function loadSitePages(): void
    {
        $this->sitePages = CmsPage::where('cms_website_id', $this->website->id)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'is_homepage', 'is_published', 'hide_from_nav', 'sort_order', 'page_theme', 'page_layout'])
            ->toArray();
    }

    public function loadPageVersions(): void
    {
        $this->pageVersions = CmsPageVersion::where('cms_page_id', $this->page->id)
            ->latest('version_number')->take(8)
            ->get(['id', 'version_number', 'change_summary', 'created_at'])
            ->toArray();
    }

    public function loadSchoolTemplates(): void
    {
        $this->schoolTemplates = CmsReusableBlock::query()
            ->where('school_id', $this->page->school_id)
            ->where('category', 'school_template')
            ->latest()
            ->get(['id', 'name', 'content'])
            ->map(fn (CmsReusableBlock $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'content' => $template->content ?? [],
            ])
            ->all();
    }

    public function beginEditingPage(int $pageId): void
    {
        $target = CmsPage::where('cms_website_id', $this->website->id)->findOrFail($pageId);
        $this->editingPageId = $target->id;
        $this->editingPageTitle = $target->title;
        $this->editingPageSlug = $target->slug;
        $this->editingPageHidden = (bool) $target->hide_from_nav;
    }

    public function savePageSettings(): void
    {
        $this->validate([
            'editingPageId' => 'required|integer',
            'editingPageTitle' => 'required|string|min:2|max:80',
            'editingPageSlug' => 'required|string|min:1|max:100',
        ]);

        $target = CmsPage::where('cms_website_id', $this->website->id)->findOrFail($this->editingPageId);
        $slug = Str::slug($this->editingPageSlug);
        $exists = CmsPage::where('cms_website_id', $this->website->id)->where('slug', $slug)->where('id', '!=', $target->id)->exists();
        if ($exists) {
            $this->addError('editingPageSlug', 'That URL is already in use by another page.');

            return;
        }

        $target->update(['title' => $this->editingPageTitle, 'slug' => $slug, 'hide_from_nav' => $this->editingPageHidden]);
        $this->editingPageId = null;
        $this->loadSitePages();
        $this->syncNavigationMenu();
        $this->dispatch('notify', ['message' => 'Page settings saved.', 'type' => 'success']);
    }

    public function togglePageVisibility(int $pageId): void
    {
        $target = CmsPage::where('cms_website_id', $this->website->id)->findOrFail($pageId);
        $target->update(['hide_from_nav' => ! $target->hide_from_nav]);
        $this->loadSitePages();
        $this->syncNavigationMenu();
    }

    public function reorderPages(array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $order => $id) {
            CmsPage::where('cms_website_id', $this->website->id)->where('id', $id)->update(['sort_order' => $order]);
        }
        $this->loadSitePages();
        $this->syncNavigationMenu();
    }

    /** Swap a page one position up/down in the navigation order. */
    public function movePage(int $pageId, string $direction): void
    {
        $pages = CmsPage::where('cms_website_id', $this->website->id)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'sort_order']);

        $index = $pages->search(fn ($p) => (int) $p->id === $pageId);
        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($pages[$targetIndex])) {
            return;
        }

        // Swap sort orders between neighbours, then normalise 0..n-1.
        $a = $pages[$index];
        $b = $pages[$targetIndex];
        CmsPage::where('id', $a->id)->update(['sort_order' => $b->sort_order]);
        CmsPage::where('id', $b->id)->update(['sort_order' => $a->sort_order]);

        $this->loadSitePages();
        $this->syncNavigationMenu();
    }

    public function restoreVersion(int $versionId): void
    {
        $version = CmsPageVersion::where('cms_page_id', $this->page->id)->findOrFail($versionId);
        $this->blocks = $version->blocks ?? [];
        $this->selectedBlockIndex = null;
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => "Version {$version->version_number} restored to your draft. Apply changes when ready.", 'type' => 'success']);
    }

    public function setEditingMode(string $mode): void
    {
        if (! in_array($mode, ['simple', 'advanced'], true)) {
            return;
        }

        $this->editingMode = $mode;
        $this->website->update([
            'theme_overrides' => array_merge($this->website->theme_overrides ?? [], ['editor_mode' => $mode]),
        ]);
        $this->dispatch('notify', ['message' => $mode === 'simple' ? 'Simple customization enabled.' : 'Advanced customization enabled.', 'type' => 'success']);
    }

    public function saveAsSchoolTemplate(): void
    {
        $this->validate(['schoolTemplateName' => 'required|string|min:3|max:100']);

        CmsReusableBlock::create([
            'school_id' => $this->page->school_id,
            'name' => $this->schoolTemplateName,
            'category' => 'school_template',
            'content' => [
                'blocks' => $this->blocks,
                'page_settings' => $this->page->page_settings ?? [],
                'source_page' => $this->page->title,
                'site_theme' => [
                    'activeTemplate' => $this->activeTemplate,
                    'color_primary' => $this->color_primary,
                    'color_secondary' => $this->color_secondary,
                    'color_accent' => $this->color_accent,
                    'color_background' => $this->color_background,
                    'color_text' => $this->color_text,
                    'color_card_bg' => $this->color_card_bg,
                    'font_primary' => $this->font_primary,
                    'font_secondary' => $this->font_secondary,
                    'design_radius' => $this->design_radius,
                    'design_shadow' => $this->design_shadow,
                    'design_container' => $this->design_container,
                    'design_button_style' => $this->design_button_style,
                ],
            ],
        ]);

        $this->schoolTemplateName = '';
        $this->loadSchoolTemplates();
        $this->dispatch('notify', ['message' => 'Saved to My Templates — styling will carry over automatically.', 'type' => 'success']);
    }

    public function applySchoolTemplate(int $templateId): void
    {
        $template = CmsReusableBlock::query()
            ->where('school_id', $this->page->school_id)
            ->where('category', 'school_template')
            ->findOrFail($templateId);

        $content = $template->content ?? [];

        $this->blocks = data_get($content, 'blocks', []);
        $this->page->update(['page_settings' => array_merge($this->page->page_settings ?? [], data_get($content, 'page_settings', []))]);

        // Restore the saved site-wide theme so the styling carries over automatically.
        $theme = data_get($content, 'site_theme', []);
        foreach ($theme as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->saveWebsiteStyles();

        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => 'School template applied — content is ready to edit, styling carried over.', 'type' => 'success']);
    }

    public function duplicateSchoolTemplate(int $templateId): void
    {
        $template = CmsReusableBlock::query()->where('school_id', $this->page->school_id)->where('category', 'school_template')->findOrFail($templateId);
        $copy = $template->replicate();
        $copy->name .= ' (Copy)';
        $copy->save();
        $this->loadSchoolTemplates();
    }

    public function deleteSchoolTemplate(int $templateId): void
    {
        CmsReusableBlock::query()->where('school_id', $this->page->school_id)->where('category', 'school_template')->findOrFail($templateId)->delete();
        $this->loadSchoolTemplates();
        $this->dispatch('notify', ['message' => 'School template deleted.', 'type' => 'info']);
    }

    // ---------------------------------------------------------------------
    // Website Templates Hub
    // ---------------------------------------------------------------------

    /** Open the studio on a template's draft (its shadow website). */
    public function editTemplate(int $templateId): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        $template = CmsSiteTemplate::query()->where('school_id', $schoolId)->findOrFail($templateId);
        $home = CmsPage::query()->where('cms_website_id', $template->cms_website_id)->where('is_homepage', true)->first()
            ?? CmsPage::query()->where('cms_website_id', $template->cms_website_id)->orderBy('sort_order')->first();

        if (! $home) {
            $this->dispatch('notify', ['message' => 'This template has no pages to edit yet.', 'type' => 'error']);

            return;
        }

        $this->redirect(self::getUrl(['pageId' => $home->id]));
    }

    /** Make a premade/saved template the live public website. */
    public function applyTemplate(int $templateId): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        try {
            CmsSiteTemplateService::apply($templateId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);

            return;
        }

        $this->loadSiteTemplates();
        $this->dispatch('notify', ['message' => '🎉 Template is now live on your public website!', 'type' => 'success']);
    }

    /** "Make Live" from inside a template draft: apply the template being edited. */
    public function makeTemplateLive(): void
    {
        if (! $this->siteTemplateId) {
            $this->dispatch('notify', ['message' => 'You are editing the live website directly, not a template.', 'type' => 'info']);

            return;
        }

        $this->applyTemplate($this->siteTemplateId);
    }

    /** Duplicate a saved template (premade presets are never duplicated). */
    public function duplicateSiteTemplate(int $templateId): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        try {
            CmsSiteTemplateService::duplicate($templateId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);

            return;
        }
        $this->loadSiteTemplates();
        $this->dispatch('notify', ['message' => 'Template duplicated.', 'type' => 'success']);
    }

    /** Delete a saved template (never the premade presets, never the active one). */
    public function deleteSiteTemplate(int $templateId): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        $template = CmsSiteTemplate::query()->where('school_id', $schoolId)->find($templateId);
        if (! $template) {
            return;
        }

        $presetNames = collect(CmsTemplateService::getTemplates())->pluck('name')->all();
        if (in_array($template->name, $presetNames, true)) {
            $this->dispatch('notify', ['message' => 'Premade templates cannot be deleted.', 'type' => 'error']);

            return;
        }

        try {
            CmsSiteTemplateService::delete($templateId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);

            return;
        }
        $this->loadSiteTemplates();
        $this->dispatch('notify', ['message' => 'Template deleted.', 'type' => 'info']);
    }

    /** Save the current live website as a named site template. */
    public function createSiteTemplateFromLive(): void
    {
        $this->validate(['newSiteTemplateName' => 'required|string|min:2|max:80']);
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;

        CmsSiteTemplateService::importFromLive(trim($this->newSiteTemplateName));
        $this->newSiteTemplateName = '';
        $this->showNewSiteTemplate = false;
        $this->loadSiteTemplates();
        $this->dispatch('notify', ['message' => 'Current website saved as a template.', 'type' => 'success']);
    }

    /** Build a brand-new template from one of the five premade presets. */
    public function createSiteTemplateFromPreset(): void
    {
        $schoolId = $this->page->school_id ?? app('current_tenant')->id ?? 0;
        $preset = CmsTemplateService::getTemplates()[$this->newSiteTemplatePreset] ?? null;
        if (! $preset) {
            return;
        }

        $name = trim($this->newSiteTemplateName) ?: $preset['name'].' (Custom)';
        $template = CmsSiteTemplateService::createFromCatalog($this->newSiteTemplatePreset, $name, 'Customised from '.$preset['name']);
        $this->newSiteTemplateName = '';
        $this->showNewSiteTemplate = false;
        $this->loadSiteTemplates();
        $this->redirect(self::getUrl(['pageId' => $template->website->pages()->orderBy('sort_order')->value('id')]));
    }

    public function addPage(): void
    {
        $this->validate(['newPageTitle' => 'required|string|min:2|max:80']);

        $slug = Str::slug($this->newPageTitle);
        $unique = $slug;
        $i = 1;
        while (CmsPage::where('cms_website_id', $this->website->id)->where('slug', $unique)->exists()) {
            $unique = $slug.'-'.$i++;
        }

        $newPage = CmsPage::create([
            'school_id' => $this->page->school_id,
            'cms_website_id' => $this->website->id,
            'title' => $this->newPageTitle,
            'slug' => $unique,
            'blocks' => [],
            'draft_blocks' => [],
            'is_homepage' => false,
            // A newly created page is immediately a usable navigation destination.
            // Its content remains a draft until the editor publishes changes.
            'is_published' => true,
            'sort_order' => count($this->sitePages),
        ]);

        $this->newPageTitle = '';
        $this->loadSitePages();
        $this->syncNavigationMenu();
        $this->dispatch('notify', ['message' => "Page \"{$newPage->title}\" created.", 'type' => 'success']);
        $this->redirect(self::getUrl(['pageId' => $newPage->id]));
    }

    public function duplicatePage(int $pageId): void
    {
        $source = CmsPage::where('cms_website_id', $this->website->id)->findOrFail($pageId);
        $slug = $source->slug.'-copy';
        $unique = $slug;
        $i = 1;
        while (CmsPage::where('school_id', $this->page->school_id)->where('slug', $unique)->exists()) {
            $unique = $slug.'-'.$i++;
        }

        CmsPage::create([
            'school_id' => $source->school_id,
            'cms_website_id' => $this->website->id,
            'title' => $source->title.' (Copy)',
            'slug' => $unique,
            'blocks' => $source->blocks,
            'draft_blocks' => $source->draft_blocks ?? $source->blocks,
            'page_theme' => $source->page_theme,
            'page_layout' => $source->page_layout,
            'is_homepage' => false,
            'is_published' => false,
            'sort_order' => count($this->sitePages),
        ]);

        $this->loadSitePages();
        $this->syncNavigationMenu();
        $this->dispatch('notify', ['message' => 'Page duplicated.', 'type' => 'success']);
    }

    public function setHomepage(int $pageId): void
    {
        CmsPage::where('cms_website_id', $this->website->id)->update(['is_homepage' => false]);
        CmsPage::where('id', $pageId)->update(['is_homepage' => true]);
        $this->loadSitePages();
        $this->syncNavigationMenu();
        $this->dispatch('notify', ['message' => 'Homepage updated.', 'type' => 'success']);
    }

    public function deletePage(int $pageId): void
    {
        if (count($this->sitePages) <= 1) {
            $this->dispatch('notify', ['message' => 'A site requires at least one page.', 'type' => 'error']);

            return;
        }

        $isCurrentPage = (int) $this->page->id === $pageId;
        CmsPage::where('id', $pageId)->where('cms_website_id', $this->website->id)->delete();
        $this->loadSitePages();
        $this->syncNavigationMenu();

        if ($isCurrentPage) {
            $remaining = CmsPage::where('cms_website_id', $this->website->id)
                ->orderBy('sort_order')->first();
            $this->redirect(self::getUrl(['pageId' => $remaining->id ?? $this->website->id]));

            return;
        }

        $this->dispatch('notify', ['message' => 'Page deleted.', 'type' => 'info']);
    }

    public function saveSeoSettings(): void
    {
        $this->page->update([
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_keywords' => $this->seoKeywords,
        ]);
        $this->showSeoModal = false;
        $this->dispatch('notify', ['message' => 'SEO Metadata saved.', 'type' => 'success']);
    }

    public function publishPage(): void
    {
        // A publish must include any inspector field the user is currently editing.
        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
        }
        $this->saveWebsiteStyles();
        $this->syncDraft();
        $nextVer = ($this->page->version ?? 0) + 1;
        CmsPageVersion::create([
            'school_id' => $this->page->school_id,
            'cms_page_id' => $this->page->getKey(),
            'cms_website_id' => $this->website->getKey(),
            'version_number' => $nextVer,
            'title' => $this->page->title,
            'slug' => $this->page->slug,
            'blocks' => $this->blocks,
            'created_by' => Auth::id(),
            'change_summary' => 'Published live via Website Builder',
        ]);

        $this->page->update([
            'blocks' => $this->blocks,
            'is_published' => true,
            'version' => $nextVer,
            'published_at' => now(),
            'published_by' => Auth::id(),
        ]);

        $this->hasUnpublishedChanges = false;
        $this->syncPageToLive();
        $this->loadPageVersions();
        $this->dispatch('notify', ['message' => '🎉 Page published live!', 'type' => 'success']);
    }

    public function discardDraft(): void
    {
        $this->blocks = $this->page->blocks ?? [];
        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];
        $this->syncDraft();
        $this->pushToHistory();
        $this->dispatch('notify', ['message' => 'Draft changes discarded.', 'type' => 'info']);
    }
}
