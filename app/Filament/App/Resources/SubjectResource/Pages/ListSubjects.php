<?php

namespace App\Filament\App\Resources\SubjectResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\SubjectResource;
use App\Services\Csv\SubjectCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubjects extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = SubjectResource::class;

    protected static function csvService(): string
    {
        return SubjectCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
