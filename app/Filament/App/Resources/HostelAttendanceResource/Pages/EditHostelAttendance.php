<?php

namespace App\Filament\App\Resources\HostelAttendanceResource\Pages;

use App\Filament\App\Resources\HostelAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostelAttendance extends EditRecord
{
    protected static string $resource = HostelAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
