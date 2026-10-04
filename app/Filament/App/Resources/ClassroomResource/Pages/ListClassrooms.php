<?php

namespace App\Filament\App\Resources\ClassroomResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ClassroomResource;
use App\Services\Csv\ClassroomCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassrooms extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = ClassroomResource::class;

    protected static function csvService(): string
    {
        return ClassroomCsvService::class;
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
