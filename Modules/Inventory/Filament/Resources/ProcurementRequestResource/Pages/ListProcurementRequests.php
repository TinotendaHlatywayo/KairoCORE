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

    protected function csvUploadHelperText(): ?string
    {
        return __("Download the template below, then add one request per row. Request Number and Requester are generated automatically. Put each requested item in the Item Name(s) column — separate multiple items with a semicolon (;), pipe (|) or a new line, and line up quantities and unit costs the same way.");
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
