<?php

namespace App\Filament\App\Resources\StudentMedicalRecordResource\Pages;

use App\Filament\App\Resources\StudentMedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentMedicalRecord extends EditRecord
{
    protected static string $resource = StudentMedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
