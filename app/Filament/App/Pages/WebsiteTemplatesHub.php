<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Pages\Page;
use Modules\CMS\Models\CmsSiteTemplate;
use Modules\CMS\Services\CmsSiteTemplateService;
use Modules\CMS\Services\CmsTemplateService;

/**
 * Website Templates hub: create / duplicate / delete named site templates,
 * set the active template, and open the design studio or content manager.
 */
class WebsiteTemplatesHub extends Page
{
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static string $view = 'livewire.cms.website-templates-hub';

    protected static ?string $slug = 'cms/templates';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Website';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Website Templates';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $title = 'Website Templates';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 1;

    public array $templates = [];

    public array $themePresets = [];

    public ?int $activeTemplateId = null;

    public int $livePageCount = 0;

    // Create template form
    public bool $showCreateForm = false;

    public string $newTemplateName = '';

    public string $newTemplateDescription = '';

    public string $newTemplatePreset = 'heritage-editorial';

    // Two-step delete confirmation
    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->themePresets = collect(CmsTemplateService::getTemplates())
            ->mapWithKeys(fn (array $template, string $key) => [$key => $template['name']])
            ->all();

        $this->loadTemplates();
    }

    public function loadTemplates(): void
    {
        $schoolId = app('current_tenant')->id ?? 0;

        $live = CmsSiteTemplateService::liveWebsite();
        $this->activeTemplateId = $live?->active_site_template_id;
        $this->livePageCount = $live?->pages()->count() ?? 0;

        $this->templates = CmsSiteTemplate::query()
            ->where('school_id', $schoolId)
            ->with('website.pages')
            ->orderBy('name')
            ->get()
            ->map(function (CmsSiteTemplate $template) {
                $home = $template->website
                    ? $template->website->pages()->orderByDesc('is_homepage')->orderBy('sort_order')->first()
                    : null;

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_active' => (int) $template->id === $this->activeTemplateId,
                    'page_count' => $template->website?->pages()->count() ?? 0,
                    'home_page_id' => $home?->id,
                    'updated_at' => $template->updated_at?->diffForHumans(),
                ];
            })
            ->all();
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
        $this->newTemplateName = '';
        $this->newTemplateDescription = '';
        $this->newTemplatePreset = array_key_first($this->themePresets);
    }

    public function createTemplate(): void
    {
        $this->validate([
            'newTemplateName' => 'required|string|min:3|max:100',
            'newTemplatePreset' => 'required|string|max:80',
        ]);

        $template = CmsSiteTemplateService::create(
            $this->newTemplateName,
            $this->newTemplatePreset,
            $this->newTemplateDescription ?: null,
        );

        $this->showCreateForm = false;
        $this->loadTemplates();

        $this->dispatch('notify', [
            'message' => "Template \"{$template->name}\" created. Open the design studio to pick a layout per page and customize.",
            'type' => 'success',
        ]);
    }

    public function applyTemplate(int $templateId): void
    {
        try {
            $template = CmsSiteTemplateService::apply($templateId);
            $this->loadTemplates();
            $this->dispatch('notify', [
                'message' => "{$template->name} is now live on your public website.",
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    public function duplicateTemplate(int $templateId): void
    {
        $copy = CmsSiteTemplateService::duplicate($templateId);
        $this->loadTemplates();
        $this->dispatch('notify', ['message' => "Duplicated as \"{$copy->name}\".", 'type' => 'success']);
    }

    public function confirmDeleteTemplate(int $templateId): void
    {
        $this->pendingDeleteId = $templateId;
    }

    public function deleteTemplate(): void
    {
        if (! $this->pendingDeleteId) {
            return;
        }

        try {
            CmsSiteTemplateService::delete($this->pendingDeleteId);
            $this->pendingDeleteId = null;
            $this->loadTemplates();
            $this->dispatch('notify', ['message' => 'Template deleted.', 'type' => 'info']);
        } catch (\RuntimeException $e) {
            $this->pendingDeleteId = null;
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    public function cancelDeleteTemplate(): void
    {
        $this->pendingDeleteId = null;
    }

    public function importFromLive(): void
    {
        $base = CmsSiteTemplateService::liveWebsite()
            ? 'Copy of my live website'
            : 'My first template';

        $template = CmsSiteTemplateService::importFromLive($base);
        $this->loadTemplates();

        $this->dispatch('notify', [
            'message' => "Template \"{$template->name}\" created from your live website content.",
            'type' => 'success',
        ]);
    }
}
