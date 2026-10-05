<?php

namespace App\Filament\App\Resources\HostelRoomResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\HostelRoomResource;
use App\Services\Csv\HostelRoomCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelRooms extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = HostelRoomResource::class;

    protected static function csvService(): string
    {
        return HostelRoomCsvService::class;
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
