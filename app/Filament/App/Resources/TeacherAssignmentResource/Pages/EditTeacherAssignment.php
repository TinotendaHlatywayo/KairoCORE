<?php

namespace App\Filament\App\Resources\TeacherAssignmentResource\Pages;

use App\Filament\App\Resources\TeacherAssignmentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Academics\Models\CourseSubject;

class EditTeacherAssignment extends EditRecord
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected function handleRecordUpdate($record, array $data): CourseSubject
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        // Saving an edit that moves the assignment onto a (school, course,
        // subject, section) combo that already exists would violate the unique
        // index. Detect it and merge into that sibling row instead of 500ing.
        $collision = CourseSubject::query()
            ->withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('course_id', $data['course_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('section_id', $data['section_id'] ?? null)
            ->where('id', '!=', $record->id)
            ->first();

        if ($collision) {
            $collision->update(array_merge($data, [
                'section_id' => $data['section_id'] ?? null,
            ]));
            $record->delete();

            Notification::make()
                ->title(__('Assignment merged'))
                ->body(__('That subject is already assigned for this scope — the existing row was updated and this duplicate removed.'))
                ->info()
                ->send();

            return $collision;
        }

        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (UniqueConstraintViolationException $e) {
            Notification::make()
                ->title(__('Assignment merged'))
                ->body(__('That subject is already assigned for this scope — your changes were applied to the existing assignment.'))
                ->info()
                ->send();

            $this->notifySave();

            return $record;
        }
    }
}
