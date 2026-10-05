<?php

namespace App\Filament\App\Resources\AssessmentMarkResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\AssessmentMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentMarks extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = AssessmentMarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
