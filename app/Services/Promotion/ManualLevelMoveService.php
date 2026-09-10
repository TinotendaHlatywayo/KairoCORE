<?php

namespace App\Services\Promotion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class ManualLevelMoveService
{
    public function move(
        int $studentId,
        int $sourceYearId,
        int $targetYearId,
        int $targetCourseId,
        int $targetSectionId,
        ?string $reason = null,
        ?int $performedBy = null,
    ): Enrollment {
        $results = $this->moveBulk(
            [$studentId],
            $sourceYearId,
            $targetYearId,
            $targetCourseId,
            $targetSectionId,
            $reason,
            $performedBy,
        );

        $enrollment = $results->first();

        if (! $enrollment) {
            throw new \RuntimeException("Student {$studentId} could not be moved (already enrolled in target year or no active source enrollment).");
        }

        return $enrollment;
    }

    public function moveBulk(
        array $studentIds,
        int $sourceYearId,
        int $targetYearId,
        int $targetCourseId,
        int $targetSectionId,
        ?string $reason = null,
        ?int $performedBy = null,
    ): Collection {
        $studentIds = array_values(array_unique($studentIds));

        return DB::transaction(function () use ($studentIds, $sourceYearId, $targetYearId, $targetCourseId, $targetSectionId, $reason, $performedBy) {
            $targetCourse = Course::withoutGlobalScopes()->findOrFail($targetCourseId);
            $targetSection = Section::withoutGlobalScopes()->findOrFail($targetSectionId);

            $this->validateSectionBelongsToCourse($targetCourse, $targetSection);
            $this->validateCapacity($targetSection, count($studentIds));

            $activeEnrollments = Enrollment::withoutGlobalScopes()
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $sourceYearId)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->get()
                ->keyBy('student_id');

            $this->validateSourceEnrollments($studentIds, $activeEnrollments);

            $now = Carbon::now();
            $newEnrollments = collect();

            foreach ($studentIds as $studentId) {
                $existing = Enrollment::withoutGlobalScopes()
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $targetYearId)
                    ->exists();

                if ($existing) {
                    continue;
                }

                $oldEnrollment = $activeEnrollments->get($studentId);

                if ($oldEnrollment) {
                    $oldEnrollment->update([
                        'status' => Enrollment::STATUS_PROMOTED,
                        'effective_date' => $now,
                        'reason' => $reason ?? 'Manual level move to ' . $targetCourse->name,
                        'performed_by_id' => $performedBy,
                    ]);
                }

                $newEnrollment = Enrollment::create([
                    'school_id' => $oldEnrollment?->school_id ?? $targetCourse->school_id,
                    'student_id' => $studentId,
                    'academic_year_id' => $targetYearId,
                    'course_id' => $targetCourseId,
                    'section_id' => $targetSectionId,
                    'roll_number' => $oldEnrollment?->roll_number,
                    'term_id' => $oldEnrollment?->term_id,
                    'status' => Enrollment::STATUS_ACTIVE,
                    'effective_date' => $now,
                    'reason' => $reason ?? 'Manual level move to ' . $targetCourse->name,
                    'performed_by_id' => $performedBy,
                ]);

                $newEnrollments->push($newEnrollment);
            }

            return $newEnrollments;
        });
    }

    protected function validateSectionBelongsToCourse(Course $course, Section $section): void
    {
        if ($section->course_id !== $course->id) {
            throw new \InvalidArgumentException(
                "Section {$section->getFullNameAttribute()} does not belong to course {$course->name}."
            );
        }
    }

    protected function validateCapacity(Section $section, int $adding): void
    {
        $targetSize = $section->target_size ?? $section->capacity;

        if (! $targetSize) {
            return;
        }

        $current = Enrollment::withoutGlobalScopes()
            ->where('section_id', $section->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->count();

        if (($current + $adding) > $targetSize) {
            throw new \OverflowException(
                "Target section {$section->getFullNameAttribute()} cannot hold {$adding} more students ({$current}/{$targetSize})."
            );
        }
    }

    protected function validateSourceEnrollments(array $studentIds, Collection $activeEnrollments): void
    {
        $missing = array_values(array_diff($studentIds, $activeEnrollments->keys()->all()));

        if ($missing !== []) {
            throw new \RuntimeException(
                'No active source enrollment for student(s): ' . implode(', ', $missing)
            );
        }
    }
}