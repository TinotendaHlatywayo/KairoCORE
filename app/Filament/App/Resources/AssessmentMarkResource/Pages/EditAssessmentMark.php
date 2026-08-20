<?php

namespace App\Filament\App\Resources\AssessmentMarkResource\Pages;

use App\Filament\App\Resources\AssessmentMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentMark extends EditRecord
{
    protected static string $resource = AssessmentMarkResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
