<?php

namespace App\Filament\App\Resources\CourseResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\CourseResource;
use App\Services\Csv\CourseCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = CourseResource::class;

    protected static function csvService(): string
    {
        return CourseCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
            $this->getHelpAction(),
        ]);
    }
}
