<?php

namespace App\Filament\App\Resources\DigitalAssessmentResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\DigitalAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitalAssessments extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = DigitalAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
