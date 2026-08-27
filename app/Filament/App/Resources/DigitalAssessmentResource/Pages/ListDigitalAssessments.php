<?php

namespace App\Filament\App\Resources\DigitalAssessmentResource\Pages;

use App\Filament\App\Resources\DigitalAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitalAssessments extends ListRecords
{
    protected static string $resource = DigitalAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
