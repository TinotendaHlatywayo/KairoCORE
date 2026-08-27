<?php

namespace App\Filament\App\Resources\DigitalAssessmentResource\Pages;

use App\Filament\App\Resources\DigitalAssessmentResource;
use Filament\Resources\Pages\CreateRecord;
use Modules\DigitalAssessment\Services\DigitalAssessmentService;

class CreateDigitalAssessment extends CreateRecord
{
    protected static string $resource = DigitalAssessmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] = current_tenant()?->id ?? auth()->user()->school_id;
        $data['created_by_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->data;
        $data['adaptive_mode'] = $this->record->adaptive_mode;
        $data['adaptive_base_difficulty'] = $this->record->adaptive_base_difficulty;
        $data['adaptive_window_size'] = $this->record->adaptive_window_size;
        $data['adaptive_adjustment_rate'] = $this->record->adaptive_adjustment_rate;

        app(DigitalAssessmentService::class)->syncAdaptiveConfig($this->record, $data);
    }
}
