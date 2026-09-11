<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Resources\StudentResource;
use App\Services\AdmissionNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $academicYearId = $data['academic_year_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $sectionId = $data['section_id'] ?? null;
        $rollNumber = $data['roll_number'] ?? null;
        $parentEmail = $data['parent_email'] ?? null;
        $waiverId = $data['fee_waiver_id'] ?? null;

        unset($data['academic_year_id'], $data['course_id'], $data['section_id'], $data['roll_number'], $data['fee_waiver_id']);

        $student = static::getModel()::create($data);

        if ($academicYearId && $courseId && $sectionId) {
            Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
                'course_id' => $courseId,
                'section_id' => $sectionId,
                'roll_number' => $rollNumber,
            ]);
        }

        if ($waiverId) {
            $student->waivers()->sync([$waiverId]);
        }

        app(AdmissionNotificationService::class)->send($student, $parentEmail, $student->school_id);

        return $student;
    }
}
