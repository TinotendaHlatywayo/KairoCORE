<?php

namespace App\Services\Academic;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class ClassAssignmentService
{
    public function reassign(
        int $studentId,
        int $fromSectionId,
        int $toSectionId,
        int $academicYearId,
        ?string $reason = null,
        ?int $performedBy = null,
    ): Enrollment {
        return DB::transaction(function () use ($studentId, $fromSectionId, $toSectionId, $academicYearId, $reason, $performedBy) {
            $student = Student::findOrFail($studentId);
            $fromSection = Section::findOrFail($fromSectionId);
            $toSection = Section::findOrFail($toSectionId);

            $this->validateSameCourse($fromSection, $toSection);
            $this->validateDifferentSection($fromSectionId, $toSectionId);

            $enrollment = $this->findActiveEnrollment($studentId, $fromSectionId, $academicYearId);

            $this->validateCapacity($toSection);

            $now = Carbon::now();

            $enrollment->update([
                'status' => Enrollment::STATUS_TRANSFERRED_OUT,
                'effective_date' => $now,
                'reason' => $reason ?? 'Transferred to ' . $toSection->getFullNameAttribute(),
                'performed_by_id' => $performedBy,
            ]);

            return Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'course_id' => $toSection->course_id,
                'section_id' => $toSectionId,
                'roll_number' => $enrollment->roll_number,
                'term_id' => $enrollment->term_id,
                'status' => Enrollment::STATUS_ACTIVE,
                'effective_date' => $now,
                'reason' => $reason ?? 'Transferred from ' . $fromSection->getFullNameAttribute(),
                'performed_by_id' => $performedBy,
            ]);
        });
    }

    public function reassignBulk(
        array $studentIds,
        int $fromSectionId,
        int $toSectionId,
        int $academicYearId,
        ?string $reason = null,
        ?int $performedBy = null,
    ): Collection {
        $studentIds = array_values(array_unique($studentIds));

        return DB::transaction(function () use ($studentIds, $fromSectionId, $toSectionId, $academicYearId, $reason, $performedBy) {
            $fromSection = Section::findOrFail($fromSectionId);
            $toSection = Section::findOrFail($toSectionId);

            $this->validateSameCourse($fromSection, $toSection);
            $this->validateDifferentSection($fromSectionId, $toSectionId);

            $enrollments = $this->findActiveEnrollments($studentIds, $fromSectionId, $academicYearId);
            $countBefore = $enrollments->count();

            $this->validateBulkInput($studentIds, $enrollments);
            $this->validateCapacityBulk($toSection, $countBefore);

            $now = Carbon::now();
            $newEnrollments = collect();

            foreach ($enrollments as $enrollment) {
                $enrollment->update([
                    'status' => Enrollment::STATUS_TRANSFERRED_OUT,
                    'effective_date' => $now,
                    'reason' => $reason ?? 'Transferred to ' . $toSection->getFullNameAttribute(),
                    'performed_by_id' => $performedBy,
                ]);

                $newEnrollment = Enrollment::create([
                    'school_id' => $enrollment->school_id,
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $academicYearId,
                    'course_id' => $toSection->course_id,
                    'section_id' => $toSectionId,
                    'roll_number' => $enrollment->roll_number,
                    'term_id' => $enrollment->term_id,
                    'status' => Enrollment::STATUS_ACTIVE,
                    'effective_date' => $now,
                    'reason' => $reason ?? 'Transferred from ' . $fromSection->getFullNameAttribute(),
                    'performed_by_id' => $performedBy,
                ]);

                $newEnrollments->push($newEnrollment);
            }

            return $newEnrollments;
        });
    }

    protected function validateSameCourse(Section $fromSection, Section $toSection): void
    {
        if ($fromSection->course_id !== $toSection->course_id) {
            throw new \InvalidArgumentException(
                'Cannot reassign between different levels: '
                . $fromSection->course_id . ' → ' . $toSection->course_id
            );
        }
    }

    protected function validateDifferentSection(int $fromSectionId, int $toSectionId): void
    {
        if ($fromSectionId === $toSectionId) {
            throw new \InvalidArgumentException('Source and target sections are the same.');
        }
    }

    protected function findActiveEnrollment(int $studentId, int $sectionId, int $academicYearId): Enrollment
    {
        $enrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->first();

        if (! $enrollment) {
            throw new \RuntimeException(
                "No active enrollment found for student {$studentId} in section {$sectionId} (year {$academicYearId})."
            );
        }

        return $enrollment;
    }

    protected function findActiveEnrollments(array $studentIds, int $sectionId, int $academicYearId): Collection
    {
        return Enrollment::withoutGlobalScopes()
            ->whereIn('student_id', $studentIds)
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->get();
    }

    protected function validateBulkInput(array $requestedIds, Collection $foundEnrollments): void
    {
        $foundIds = $foundEnrollments->pluck('student_id')->values()->all();
        $missing = array_diff($requestedIds, $foundIds);

        if ($missing !== []) {
            throw new \RuntimeException(
                'Students not found in source section (no active enrollment): ' . implode(', ', $missing)
            );
        }
    }

    protected function validateCapacity(Section $toSection): void
    {
        $targetSize = $toSection->target_size ?? $toSection->capacity;

        if (! $targetSize) {
            return;
        }

        $currentCount = Enrollment::withoutGlobalScopes()
            ->where('section_id', $toSection->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->count();

        if ($currentCount >= $targetSize) {
            throw new \OverflowException(
                "Target section {$toSection->getFullNameAttribute()} is at capacity ({$currentCount}/{$targetSize})."
            );
        }
    }

    protected function validateCapacityBulk(Section $toSection, int $addingCount): void
    {
        $targetSize = $toSection->target_size ?? $toSection->capacity;

        if (! $targetSize) {
            return;
        }

        $currentCount = Enrollment::withoutGlobalScopes()
            ->where('section_id', $toSection->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->count();

        if (($currentCount + $addingCount) > $targetSize) {
            throw new \OverflowException(
                "Bulk transfer would exceed capacity: adding {$addingCount} students to "
                . "{$toSection->getFullNameAttribute()} ({$currentCount} existing, {$targetSize} max)."
            );
        }
    }
}
