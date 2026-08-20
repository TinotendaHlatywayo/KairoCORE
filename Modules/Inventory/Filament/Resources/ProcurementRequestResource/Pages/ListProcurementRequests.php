<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Services\Csv\ProcurementRequestCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;

class ListProcurementRequests extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = ProcurementRequestResource::class;

    protected static function csvService(): string
    {
        return ProcurementRequestCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
