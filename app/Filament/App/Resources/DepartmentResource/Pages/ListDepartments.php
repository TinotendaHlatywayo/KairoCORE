<?php

namespace App\Filament\App\Resources\DepartmentResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\DepartmentResource;
use App\Services\Csv\DepartmentCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = DepartmentResource::class;

    protected static function csvService(): string
    {
        return DepartmentCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
            ...$this->csvBulkActions(),
        ];
    }
}
