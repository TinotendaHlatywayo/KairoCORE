<?php

namespace App\Filament\App\Resources\DigitalAssessmentResource\Pages;

use App\Filament\App\Resources\DigitalAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\DigitalAssessment\Services\DigitalAssessmentService;

class EditDigitalAssessment extends EditRecord
{
    protected static string $resource = DigitalAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(DigitalAssessmentService::class)->syncAdaptiveConfig($this->record, $this->data);
    }
}
