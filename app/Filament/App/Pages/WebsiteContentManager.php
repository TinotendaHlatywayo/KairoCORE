<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Modules\CMS\Models\CmsMedia;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsSiteTemplateService;
use Modules\CMS\Services\CmsTemplateService;

/**
 * Content Manager: edit ONLY the content of the currently active template
 * (headings, text, images, CTA labels, card collections). Design is locked.
 * Edits save to the template and auto-publish to the live website, with an
 * embedded live preview that updates as you type.
 */
class WebsiteContentManager extends Page
{
    use ModulePermissionAccess;
    use WithFileUploads;

    protected static string $view = 'livewire.cms.website-content-manager';

    protected static ?string $slug = 'cms/content';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Website';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Content Manager';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $title = 'Website Content Manager';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 2;

    /** Plain-text / URL content keys (styles are deliberately excluded). */
    protected const SCALAR_FIELDS = [
        'title', 'description', 'cta_text', 'cta_url',
        'secondary_cta_text', 'secondary_cta_url',
        'principal_name', 'principal_title', 'mission', 'vision',
        'address', 'phone', 'email', 'image_url', 'video_url', 'map_url',
    ];

    /** Nested collections that are pure content (not design). */
    protected const COLLECTIONS = [
        'items' => ['title' => 'Title', 'desc' => 'Description'],
        'features' => ['title' => 'Title', 'desc' => 'Description'],
        'faqs' => ['q' => 'Question', 'a' => 'Answer'],
        'testimonials' => ['quote' => 'Quote', 'name' => 'Name', 'role' => 'Role'],
        'images' => ['url' => 'Image URL', 'caption' => 'Caption label'],
        'logos' => ['name' => 'Partner name', 'logo_url' => 'Logo URL'],
    ];

    public ?int $activeTemplateId = null;

    public string $activeTemplateName = '';

    public ?int $activeWebsiteId = null;

    public array $sitePages = [];

    public ?int $selectedPageId = null;

    public array $blocks = [];

    public ?int $selectedBlockIndex = null;

    public array $selectedBlockData = [];

    public string $previewSize = 'full'; // 'full', 'tablet', 'mobile'

    public $tempImage = null;

    public string $mediaAltText = '';

    public ?string $lastSavedAt = null;

    // Dynamic module aggregates for previews
    public array $stats = [];

    public array $news = [];

    public array $events = [];

    public array $staff = [];

    public array $theme = [];

    public function mount(): void
    {
        $active = CmsSiteTemplateService::active();
        if (! $active) {
            $this->activeTemplateId = null;
            $this->activeTemplateName = '';

            return;
        }

        $this->activeTemplateId = $active->id;
        $this->activeTemplateName = $active->name;
        $this->activeWebsiteId = $active->cms_website_id;

        $schoolId = app('current_tenant')->id ?? 0;
        $this->stats = CmsTemplateService::resolveDynamicBlockData('statistics', $schoolId);
        $this->news = CmsTemplateService::resolveDynamicBlockData('news_feed', $schoolId);
        $this->events = CmsTemplateService::resolveDynamicBlockData('events_calendar', $schoolId);
        $this->staff = CmsTemplateService::resolveDynamicBlockData('staff_directory', $schoolId);

        $this->loadPages();
        $this->buildTheme();
    }

    public function loadPages(): void
    {
        $this->sitePages = CmsPage::query()
            ->where('cms_website_id', $this->activeWebsiteId)
            ->orderByDesc('is_homepage')
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'is_homepage', 'is_published'])
            ->toArray();

        if ($this->sitePages) {
            $first = $this->sitePages[0]['id'];
            $this->selectPage($this->selectedPageId ?? $first);
        }
    }

    public function selectPage(int $pageId): void
    {
        $this->selectedPageId = $pageId;
        $this->selectedBlockIndex = null;
        $this->selectedBlockData = [];

        $page = CmsPage::query()->where('cms_website_id', $this->activeWebsiteId)->find($pageId);
        if (! $page) {
            $this->blocks = [];

            return;
        }

        $this->blocks = $page->draft_blocks ?? $page->blocks ?? [];
        if (is_string($this->blocks)) {
            $this->blocks = json_decode($this->blocks, true) ?? [];
        }

        if (! is_null($this->selectedBlockIndex) && isset($this->blocks[$this->selectedBlockIndex])) {
            $this->selectedBlockIndex = null;
        }
    }

    public function selectBlock(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }
        $this->selectedBlockIndex = $index;
        $this->selectedBlockData = $this->blocks[$index];
    }

    public function updatedSelectedBlockData(): void
    {
        if (is_null($this->selectedBlockIndex) || ! isset($this->blocks[$this->selectedBlockIndex])) {
            return;
        }

        $this->blocks[$this->selectedBlockIndex] = $this->selectedBlockData;
        $this->persistPage();
    }

    public function attachUploadedImage(?string $targetField = null): void
    {
        $targetField = $targetField ?? 'image_url';
        $this->validate(['tempImage' => 'required|image|max:8192']);

        if (! $this->tempImage) {
            return;
        }

        $schoolId = app('current_tenant')->id ?? 0;
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
        $this->persistPage();
    }

    /** Save the working page to the template and auto-publish content to the live site. */
    private function persistPage(): void
    {
        if (! $this->selectedPageId) {
            return;
        }

        $page = CmsPage::query()->where('cms_website_id', $this->activeWebsiteId)->find($this->selectedPageId);
        if (! $page) {
            return;
        }

        $page->update(['blocks' => $this->blocks, 'draft_blocks' => $this->blocks]);
        $this->autoPublishContent($page);
        $this->lastSavedAt = now()->format('g:i:s A');
    }

    /** Push only content keys to the matching live page block (design untouched). */
    private function autoPublishContent(CmsPage $shadowPage): void
    {
        $live = CmsSiteTemplateService::liveWebsite();
        if (! $live) {
            return;
        }

        $livePage = CmsPage::where('cms_website_id', $live->id)->where('slug', $shadowPage->slug)->first();
        if (! $livePage) {
            return;
        }

        $shadowById = collect($this->blocks)->keyBy('id');
        $contentKeys = array_merge(self::SCALAR_FIELDS, array_keys(self::COLLECTIONS));

        $liveBlocks = array_map(function (array $block) use ($shadowById, $contentKeys) {
            $source = $shadowById->get($block['id'] ?? null);
            if (! $source) {
                return $block;
            }
            foreach ($contentKeys as $key) {
                if (array_key_exists($key, $source)) {
                    $block[$key] = $source[$key];
                }
            }

            return $block;
        }, $livePage->blocks ?? []);

        $livePage->update(['blocks' => $liveBlocks]);
    }

    public function buildTheme(): void
    {
        $website = CmsWebsiteForPreview::fromTemplate($this->activeWebsiteId);
        if (! $website) {
            $this->theme = [];

            return;
        }

        $presets = CmsTemplateService::getTemplates();
        $preset = $presets[CmsTemplateService::canonicalTemplate($website->active_template)] ?? $presets['heritage-editorial'];

        $this->theme = [
            'primary' => CmsTemplateService::safeHex($website->color_primary, $preset['palette']['primary']),
            'secondary' => CmsTemplateService::safeHex($website->color_secondary, $preset['palette']['secondary']),
            'accent' => CmsTemplateService::safeHex($website->color_accent, $preset['palette']['accent']),
            'background' => CmsTemplateService::safeHex($website->color_background, '#ffffff'),
            'text' => CmsTemplateService::safeHex($website->color_text, '#0f172a'),
            'cardBg' => CmsTemplateService::safeHex($website->color_card_bg, '#f8fafc'),
            'fontPrimary' => $website->font_primary ?: $preset['fonts']['primary'],
            'fontSecondary' => $website->font_secondary ?: $preset['fonts']['secondary'],
            'container' => CmsTemplateService::CONTAINER_SCALE[
                CmsTemplateService::safeToken($website->design_container, CmsTemplateService::CONTAINER_SCALE, 'wide')
            ],
        ];
    }

    public function previewVars(): string
    {
        $theme = $this->theme;
        $radius = CmsTemplateService::RADIUS_SCALE['lg'] ?? '20px';
        $shadow = CmsTemplateService::SHADOW_SCALE['md'] ?? 'none';
        $btn = CmsTemplateService::BUTTON_STYLES['pill'] ?? 'rounded-full';

        $website = CmsWebsiteForPreview::fromTemplate($this->activeWebsiteId);
        if ($website) {
            $radius = CmsTemplateService::RADIUS_SCALE[
                CmsTemplateService::safeToken($website->design_radius, CmsTemplateService::RADIUS_SCALE, 'lg')
            ] ?? '20px';
            $shadow = CmsTemplateService::SHADOW_SCALE[
                CmsTemplateService::safeToken($website->design_shadow, CmsTemplateService::SHADOW_SCALE, 'md')
            ] ?? 'none';
            $btn = CmsTemplateService::BUTTON_STYLES[
                CmsTemplateService::safeToken($website->design_button_style, CmsTemplateService::BUTTON_STYLES, 'pill')
            ] ?? 'rounded-full';
        }

        return sprintf(
            '--theme-primary:%s; --theme-secondary:%s; --theme-accent:%s; --theme-bg:%s; --theme-text:%s; --theme-card-bg:%s; --theme-radius:%s; --theme-shadow:%s; --theme-btn-radius:%s; --font-primary:%s; --font-secondary:%s;',
            $theme['primary'] ?? '#1e3a8a',
            $theme['secondary'] ?? '#0284c7',
            $theme['accent'] ?? '#f59e0b',
            $theme['background'] ?? '#ffffff',
            $theme['text'] ?? '#0f172a',
            $theme['cardBg'] ?? '#f8fafc',
            $radius,
            $shadow,
            $btn,
            "'".($theme['fontPrimary'] ?? 'Inter')."', sans-serif",
            "'".($theme['fontSecondary'] ?? 'Outfit')."', serif"
        );
    }
}

/** Tiny helper so the content manager can build a theme from the template's shadow website. */
final class CmsWebsiteForPreview
{
    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function fromTemplate(?int $websiteId): ?CmsWebsite
    {
        if (! $websiteId) {
            return null;
        }

        return CmsWebsite::query()->find($websiteId);
    }
}
