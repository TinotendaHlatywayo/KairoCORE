<?php

namespace App\Filament\App\Resources\FeeCategoryResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\FeeCategoryResource;
use App\Services\Csv\FeeCategoryCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeCategories extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = FeeCategoryResource::class;

    protected static function csvService(): string
    {
        return FeeCategoryCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
