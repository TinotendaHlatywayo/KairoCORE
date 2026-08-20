<?php

namespace App\Filament\App\Resources\ExpenseCategoryResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\ExpenseCategoryResource;
use App\Services\Csv\ExpenseCategoryCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseCategories extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = ExpenseCategoryResource::class;

    protected static function csvService(): string
    {
        return ExpenseCategoryCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
