<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Resources\StudentResource;
use App\Services\AdmissionNotificationService;
use App\Services\RosterAccountProvisioningService;
use Filament\Notifications\Notification;
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
        $waiverId = ($data['apply_waiver'] ?? false) ? ($data['fee_waiver_id'] ?? null) : null;

        unset($data['academic_year_id'], $data['course_id'], $data['section_id'], $data['roll_number'], $data['fee_waiver_id'], $data['apply_waiver']);

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

    protected function afterCreate(): void
    {
        $user = app(RosterAccountProvisioningService::class)->provisionStudent($this->record);

        if ($user) {
            Notification::make()
                ->title(__('Portal account created'))
                ->body(__('An activation email was sent to').' '.$user->email.'.')
                ->success()
                ->send();
        } elseif (blank($this->record->getRawOriginal('email'))) {
            Notification::make()
                ->title(__('No portal account created'))
                ->body(__('Add an email address to this student to create their portal account and send the activation email.'))
                ->warning()
                ->send();
        }
    }
}
