<?php

namespace App\Filament\App\Pages\Library;

use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class CirculationHub extends Page
{
    protected static string $view = 'filament.app.pages.library.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-right';

    protected static ?string $navigationGroup = 'Library';

    protected static ?string $navigationLabel = 'Circulation';

    protected static ?string $title = 'Circulation';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'library-circulation';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('library');
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
        return __('Circulation');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('library');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session("nav.last.library.".$this->getCategoryLabel());
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
