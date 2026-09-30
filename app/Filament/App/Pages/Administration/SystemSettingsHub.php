<?php

namespace App\Filament\App\Pages\Administration;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Navigation\ModuleNavigationService;
use Filament\Pages\Page;
use App\Filament\App\Concerns\ModulePermissionAccess;

class SystemSettingsHub extends Page
{
    use ModulePermissionAccess;

    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.administration.category-hub';

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $title = 'System Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'admin-system-settings';

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
        return __('System Settings');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('administration');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && empty($t['hub'])
        ));
    }

    public function mount(): void
    {
        $last = session('nav.last.administration.'.$this->getCategoryLabel());
        $pages = $this->getCategoryPages();

        if (empty($pages)) {
            return;
        }

        $validUrls = collect($pages)->pluck('url')->all();
        $target = in_array($last, $validUrls, true) ? $last : ($pages[0]['url'] ?? null);

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
