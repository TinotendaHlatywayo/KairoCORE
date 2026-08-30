<?php

namespace App\Filament\App\Pages\Finance;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class CoreAccountingHub extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.finance.core-accounting-hub';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Core Accounting & Setup';

    protected static ?string $title = 'Core Accounting & Setup';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'finance-core-accounting';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('finance');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function mount(): void
    {
        $last = session("nav.last.finance.".$this->getCategoryLabel());
        $pages = $this->getCategoryPages();
        $target = $last ?: ($pages[0]['url'] ?? null);
        if ($target && $target !== request()->url()) {
            redirect($target);
        }
    }

    public function getCategoryLabel(): string
    {
        return __('Core Accounting & Setup');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('finance');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && ($t['class'] ?? null) !== static::class
        ));
    }

    protected function getViewData(): array
    {
        return [
            'categoryLabel' => __('Core Accounting & Setup'),
            'categoryPages' => $this->getCategoryPages(),
        ];
    }
}
