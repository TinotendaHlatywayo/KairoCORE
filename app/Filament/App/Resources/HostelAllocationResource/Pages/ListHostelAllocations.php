<?php

namespace App\Filament\App\Resources\HostelAllocationResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\HostelAllocationResource;
use App\Services\Csv\HostelAllocationCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelAllocations extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = HostelAllocationResource::class;

    protected static function csvService(): string
    {
        return HostelAllocationCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
