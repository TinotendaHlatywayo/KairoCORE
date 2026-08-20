<?php

namespace App\Filament\App\Resources\StaffAttendanceResource\Pages;

use App\Filament\App\Resources\StaffAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffAttendance extends EditRecord
{
    protected static string $resource = StaffAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
