<?php

namespace App\Filament\App\Resources\RevenueCategoryResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\RevenueCategoryResource;
use App\Services\Csv\RevenueCategoryCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenueCategories extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = RevenueCategoryResource::class;

    protected static function csvService(): string
    {
        return RevenueCategoryCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
