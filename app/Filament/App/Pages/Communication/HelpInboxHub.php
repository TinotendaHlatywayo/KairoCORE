<?php

namespace App\Filament\App\Pages\Communication;

use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class HelpInboxHub extends Page
{
    protected static string $view = 'filament.app.pages.communication.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $navigationLabel = 'Help & Inbox';

    protected static ?string $title = 'Help & Inbox';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'communication-help-inbox';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('communication');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getCategoryLabel(): string
    {
        return __('Help & Inbox');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('communication');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session('nav.last.communication.'.$this->getCategoryLabel());
        $pages = $this->getCategoryPages();
        $target = $last ?: ($pages[0]['url'] ?? null);
        if ($target && $target !== request()->url()) {
            redirect($target);
        }
    }

    protected function getViewData(): array
    {
        return [
            'categoryLabel' => $this->getTitle(),
            'categoryPages' => $this->getCategoryPages(),
        ];
    }
}
