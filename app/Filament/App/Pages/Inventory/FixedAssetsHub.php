<?php

namespace App\Filament\App\Pages\Inventory;

use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class FixedAssetsHub extends Page
{
    protected static string $view = 'filament.app.pages.inventory.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Inventory & Procurement';

    protected static ?string $navigationLabel = 'Fixed Assets';

    protected static ?string $title = 'Fixed Assets';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'inventory-fixed-assets';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('inventory');
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
        return __('Fixed Assets');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('inventory');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session("nav.last.inventory.".$this->getCategoryLabel());
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
