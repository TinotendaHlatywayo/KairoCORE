<?php

namespace App\Filament\App\Resources\StaffAttendanceResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\StaffAttendanceResource;
use App\Services\Csv\StaffAttendanceCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffAttendances extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = StaffAttendanceResource::class;

    protected static function csvService(): string
    {
        return StaffAttendanceCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
