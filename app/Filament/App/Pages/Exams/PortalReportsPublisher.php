<?php

namespace App\Filament\App\Pages\Exams;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;

class PortalReportsPublisher extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.exams.portal-reports-publisher';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationLabel = 'Publish to Student Portal';

    protected static ?string $title = 'Publish to Student Portal';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'exams-portal-reports-publisher';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('exams');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }
}