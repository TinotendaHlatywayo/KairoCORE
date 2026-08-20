<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Actions\RemoveProfilePhotoAction;
use App\Filament\App\Resources\StudentResource;
use Filament\Resources\Pages\EditRecord;
use Modules\Students\Models\Enrollment;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public function getHeaderActions(): array
    {
        return [
            RemoveProfilePhotoAction::make()
                ->photoColumn('photo_path')
                ->visible(fn () => filled($this->getRecord()->photo_path))
                ->record($this->getRecord()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $student = $this->record;
        $currentEnrollment = $student->currentEnrollment;

        if ($currentEnrollment) {
            $data['academic_year_id'] = $currentEnrollment->academic_year_id;
            $data['course_id'] = $currentEnrollment->course_id;
            $data['section_id'] = $currentEnrollment->section_id;
            $data['roll_number'] = $currentEnrollment->roll_number;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $student = $this->record;
        $formData = $this->form->getRawState();

        Enrollment::updateOrCreate(
            [
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'academic_year_id' => $formData['academic_year_id'],
            ],
            [
                'course_id' => $formData['course_id'],
                'section_id' => $formData['section_id'],
                'roll_number' => $formData['roll_number'] ?? null,
            ]
        );
    }
}
