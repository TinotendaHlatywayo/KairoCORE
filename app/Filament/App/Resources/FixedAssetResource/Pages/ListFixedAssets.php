<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\FixedAssetResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\FixedAssetResource;
use App\Services\Csv\FixedAssetCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFixedAssets extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = FixedAssetResource::class;

    protected static function csvService(): string
    {
        return FixedAssetCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
