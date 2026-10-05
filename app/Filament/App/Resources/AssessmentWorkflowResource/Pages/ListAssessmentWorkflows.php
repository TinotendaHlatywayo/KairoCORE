<?php

namespace App\Filament\App\Resources\AssessmentWorkflowResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\AssessmentWorkflowResource;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentWorkflows extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = AssessmentWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
