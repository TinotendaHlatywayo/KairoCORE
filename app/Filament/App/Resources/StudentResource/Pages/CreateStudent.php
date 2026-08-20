<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Resources\StudentResource;
use App\Services\AdmissionNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Modules\Students\Models\Enrollment;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function afterCreate(): void
    {
        $student = $this->record;
        $formData = $this->form->getRawState();

        Enrollment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'academic_year_id' => $formData['academic_year_id'],
            'course_id' => $formData['course_id'],
            'section_id' => $formData['section_id'],
            'roll_number' => $formData['roll_number'] ?? null,
        ]);

        // Send the admission confirmation email to the registered parent email.
        app(AdmissionNotificationService::class)->send($student, $formData['parent_email'] ?? null, $student->school_id);
    }
}
