<?php

namespace App\Filament\App\Resources\TeacherAssignmentResource\Pages;

use App\Filament\App\Resources\TeacherAssignmentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Subject;

class CreateTeacherAssignment extends CreateRecord
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected function handleRecordCreation(array $data): CourseSubject
    {
        $schoolId = current_tenant()?->id
            ?? auth()->user()?->school_id
            ?? ($data['school_id'] ?? null);

        $subjectId = $data['subject_id'] ?? null;

        if (! $subjectId) {
            $subjects = Subject::where('school_id', $schoolId)->get();
            if ($subjects->isEmpty()) {
                throw new \Exception(__('No subjects found for this school. Please add subjects first.'));
            }
            $lastRecord = null;
            foreach ($subjects as $subj) {
                $payload = array_merge($data, [
                    'school_id' => $schoolId,
                    'subject_id' => $subj->id,
                    'section_id' => $data['section_id'] ?? null,
                ]);
                $existing = CourseSubject::query()
                    ->withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('course_id', $data['course_id'])
                    ->where('subject_id', $subj->id)
                    ->where('section_id', $data['section_id'] ?? null)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $lastRecord = $existing;
                } else {
                    $lastRecord = CourseSubject::create($payload);
                }
            }

            Notification::make()
                ->title(__('Teacher Assigned to All Subjects'))
                ->body(__('Teacher was successfully assigned to teach all subjects for this class/course.'))
                ->success()
                ->send();

            return $lastRecord;
        }

        // The same (school, course, subject, section) pivot can only exist
        // once — a natural side-effect of the unique index. Rather than 500ing
        // on a duplicate, upsert it so saving the same form again simply
        // updates the existing row's teacher / periods / room / role.
        $existing = CourseSubject::query()
            ->withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('course_id', $data['course_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('section_id', $data['section_id'] ?? null)
            ->first();

        if ($existing) {
            $existing->update(array_merge($data, [
                'section_id' => $data['section_id'] ?? null,
            ]));

            Notification::make()
                ->title(__('Assignment updated'))
                ->body(__('That subject was already assigned for this scope — the existing teacher/periods were updated instead.'))
                ->info()
                ->send();

            return $existing;
        }

        return DB::transaction(function () use ($data, $schoolId) {
            $record = static::getModel()::create(array_merge($data, [
                'school_id' => $schoolId,
                'section_id' => $data['section_id'] ?? null,
            ]));

            return $record;
        });
    }
}
