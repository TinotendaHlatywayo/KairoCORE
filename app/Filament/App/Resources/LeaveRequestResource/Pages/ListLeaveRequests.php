<?php

namespace App\Filament\App\Resources\LeaveRequestResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\LeaveRequestResource;
use App\Services\Csv\LeaveRequestCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = LeaveRequestResource::class;

    protected static function csvService(): string
    {
        return LeaveRequestCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Leave Request')),
            ...$this->csvBulkActions(),
        ];
    }
}
