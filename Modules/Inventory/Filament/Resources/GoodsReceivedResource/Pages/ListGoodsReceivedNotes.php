<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Services\Csv\GoodsReceivedCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;

class ListGoodsReceivedNotes extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = GoodsReceivedResource::class;

    protected static function csvService(): string
    {
        return GoodsReceivedCsvService::class;
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
