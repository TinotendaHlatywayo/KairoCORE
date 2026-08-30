<?php

namespace App\Filament\App\Pages\Hr;

use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class StaffDirectoryHub extends Page
{
    protected static string $view = 'filament.app.pages.hr.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $navigationLabel = 'Staff Directory & HR';

    protected static ?string $title = 'Staff Directory & HR';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'hr-staff-directory';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('hr');
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
        return __('Staff Directory & HR');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('hr');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session("nav.last.hr.".$this->getCategoryLabel());
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
