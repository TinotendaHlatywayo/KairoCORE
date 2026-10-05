<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\StockAdjustmentResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\StockAdjustmentResource;
use App\Services\Csv\StockAdjustmentCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = StockAdjustmentResource::class;

    protected static function csvService(): string
    {
        return StockAdjustmentCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
