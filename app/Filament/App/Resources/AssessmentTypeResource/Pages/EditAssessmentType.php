<?php

namespace App\Filament\App\Resources\AssessmentTypeResource\Pages;

use App\Filament\App\Resources\AssessmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentType extends EditRecord
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
