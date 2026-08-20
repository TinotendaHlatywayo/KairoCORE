<?php

namespace App\Filament\App\Resources\StudentMedicalRecordResource\Pages;

use App\Filament\App\Resources\StudentMedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentMedicalRecords extends ListRecords
{
    protected static string $resource = StudentMedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
