<?php

namespace App\Filament\App\Pages\Exams;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Pages\Page;

class PortalReportsPublisher extends Page
{
    use HasPageHelp;
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static string $view = 'filament.app.pages.exams.portal-reports-publisher';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationLabel = 'Publish to Student Portal';

    protected static ?string $title = 'Publish to Student Portal';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'exams-portal-reports-publisher';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
