<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\AssetMaintenanceResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Services\Csv\AssetMaintenanceCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;

class ListAssetMaintenances extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = AssetMaintenanceResource::class;

    protected static function csvService(): string
    {
        return AssetMaintenanceCsvService::class;
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
