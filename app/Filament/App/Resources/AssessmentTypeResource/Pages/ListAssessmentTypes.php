<?php

namespace App\Filament\App\Resources\AssessmentTypeResource\Pages;

// This import must match the exact namespace of the parent class
use App\Filament\App\Resources\AssessmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentTypes extends ListRecords
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
