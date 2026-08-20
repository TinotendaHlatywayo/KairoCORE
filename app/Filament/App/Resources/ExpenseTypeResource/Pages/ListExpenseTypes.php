<?php

namespace App\Filament\App\Resources\ExpenseTypeResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\ExpenseTypeResource;
use App\Services\Csv\ExpenseTypeCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseTypes extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = ExpenseTypeResource::class;

    protected static function csvService(): string
    {
        return ExpenseTypeCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
