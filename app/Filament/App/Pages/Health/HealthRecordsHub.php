<?php

namespace App\Filament\App\Pages\Health;

use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class HealthRecordsHub extends Page
{
    protected static string $view = 'filament.app.pages.health.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Health & Safety';

    protected static ?string $navigationLabel = 'Health Records';

    protected static ?string $title = 'Health Records';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'health-records';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('health');
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
        return __('Health Records');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('health');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session('nav.last.health.'.$this->getCategoryLabel());
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
