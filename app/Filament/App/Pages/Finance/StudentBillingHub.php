<?php

namespace App\Filament\App\Pages\Finance;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class StudentBillingHub extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.finance.student-billing-hub';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Student Billing & Revenue (Receivables)';

    protected static ?string $title = 'Student Billing & Revenue (Receivables)';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'finance-student-billing';

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
        $last = session("finance.last.".$this->getCategoryLabel());
        if ($last && $last !== request()->url()) {
            redirect($last);
        }
    }

    public function getCategoryLabel(): string
    {
        return __('Student Billing & Revenue');
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
            'categoryLabel' => __('Student Billing & Revenue (Receivables)'),
            'categoryPages' => $this->getCategoryPages(),
        ];
    }
}
