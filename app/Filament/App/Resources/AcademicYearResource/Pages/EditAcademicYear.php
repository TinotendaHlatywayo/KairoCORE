<?php

namespace App\Filament\App\Resources\AcademicYearResource\Pages;

use App\Filament\App\Resources\AcademicYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicYear extends EditRecord
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
