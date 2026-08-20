<?php

namespace App\Filament\App\Resources\HostelResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\HostelResource;
use App\Services\Csv\HostelCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostels extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = HostelResource::class;

    protected static function csvService(): string
    {
        return HostelCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
