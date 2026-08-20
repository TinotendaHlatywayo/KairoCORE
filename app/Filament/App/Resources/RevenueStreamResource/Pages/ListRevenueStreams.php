<?php

namespace App\Filament\App\Resources\RevenueStreamResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\RevenueStreamResource;
use App\Services\Csv\RevenueStreamCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenueStreams extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = RevenueStreamResource::class;

    protected static function csvService(): string
    {
        return RevenueStreamCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
