<?php

namespace App\Services\Promotion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Promotion\Models\PromotionItem;
use Modules\Promotion\Models\PromotionRun;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class PromotionService
{
    public function preview(int $schoolId, int $sourceYearId, int $targetYearId, ?int $createdBy = null): PromotionRun
    {
        return DB::transaction(function () use ($schoolId, $sourceYearId, $targetYearId, $createdBy) {
            $run = PromotionRun::create([
                'school_id' => $schoolId,
                'source_academic_year_id' => $sourceYearId,
                'target_academic_year_id' => $targetYearId,
                'status' => PromotionRun::STATUS_DRAFT,
                'created_by_id' => $createdBy,
            ]);

            $activeEnrollments = Enrollment::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $sourceYearId)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->with('course', 'section')
                ->get();

            if ($activeEnrollments->isEmpty()) {
                $fallbackYearId = Enrollment::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->orderByDesc('id')
                    ->value('academic_year_id');

                if ($fallbackYearId) {
                    $activeEnrollments = Enrollment::withoutGlobalScopes()
                        ->where('school_id', $schoolId)
                        ->where('academic_year_id', $fallbackYearId)
                        ->where('status', Enrollment::STATUS_ACTIVE)
                        ->with('course', 'section')
                        ->get();
                }
            }

            $schoolCourses = Course::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->orderBy('sequence_order')
                ->orderBy('id')
                ->get();

            $courseIndexMap = $schoolCourses->values();

            foreach ($activeEnrollments as $enrollment) {
                $studentId = $enrollment->student_id;
                $sourceCourse = $enrollment->course;

                if (! $sourceCourse) {
                    continue;
                }

                $currentIndex = $courseIndexMap->search(fn ($c) => $c->id === $sourceCourse->id);

                $nextCourse = null;
                if ($sourceCourse->next_level_id) {
                    $nextCourse = $schoolCourses->firstWhere('id', $sourceCourse->next_level_id);
                } elseif ($currentIndex !== false && isset($courseIndexMap[$currentIndex + 1])) {
                    $nextCourse = $courseIndexMap[$currentIndex + 1];
                }

                $isTerminal = (bool) $sourceCourse->is_terminal || ! $nextCourse;

                $decision = PromotionItem::DECISION_PROMOTED;
                $targetCourseId = $isTerminal ? null : ($nextCourse?->id);
                $targetSectionId = null;

                if (! $isTerminal && $targetCourseId) {
                    $targetSectionId = $this->resolveParallelSection(
                        $schoolId,
                        $sourceCourse->id,
                        $targetCourseId,
                        $enrollment->section->name ?? null,
                    );
                }

                $decisionReason = $isTerminal
                    ? 'Terminal level ('.$sourceCourse->name.') — graduated'
                    : 'Auto-promoted via level progression ('.$sourceCourse->name.' → '.($nextCourse?->name ?? '').')';

                PromotionItem::create([
                    'school_id' => $schoolId,
                    'promotion_run_id' => $run->id,
                    'student_id' => $studentId,
                    'source_enrollment_id' => $enrollment->id,
                    'decision' => $decision,
                    'target_course_id' => $targetCourseId,
                    'target_section_id' => $targetSectionId,
                    'reason' => $decisionReason,
                ]);
            }

            return $run;
        });
    }

    public function commit(int $runId, ?int $performedBy = null): void
    {
        DB::transaction(function () use ($runId, $performedBy) {
            $run = PromotionRun::findOrFail($runId);

            if ($run->status !== PromotionRun::STATUS_DRAFT) {
                throw new \RuntimeException("Run #{$runId} is in status '{$run->status}' — only draft runs can be committed.");
            }

            $run->update(['status' => PromotionRun::STATUS_IN_PROGRESS]);

            $items = PromotionItem::where('promotion_run_id', $runId)
                ->where('decision', PromotionItem::DECISION_PROMOTED)
                ->get();

            $now = Carbon::now();

            foreach ($items as $item) {
                $oldEnrollment = $item->sourceEnrollment;

                if ($oldEnrollment) {
                    $oldEnrollment->update([
                        'status' => Enrollment::STATUS_PROMOTED,
                        'effective_date' => $now,
                        'reason' => $item->reason ?? 'Promoted in run #'.$runId,
                        'performed_by_id' => $performedBy,
                    ]);
                }

                if (! $item->target_course_id) {
                    Student::withoutGlobalScopes()
                        ->whereKey($item->student_id)
                        ->update(['status' => 'graduated']);
                }

                if ($item->target_course_id) {
                    $targetSectionId = $this->resolveTargetSectionId(
                        $run->school_id,
                        $item->target_course_id,
                        $item->target_section_id,
                    );

                    Enrollment::create([
                        'school_id' => $run->school_id,
                        'student_id' => $item->student_id,
                        'academic_year_id' => $run->target_academic_year_id,
                        'course_id' => $item->target_course_id,
                        'section_id' => $targetSectionId,
                        'roll_number' => $oldEnrollment?->roll_number,
                        'term_id' => $oldEnrollment?->term_id,
                        'status' => Enrollment::STATUS_ACTIVE,
                        'effective_date' => $now,
                        'reason' => $item->reason ?? 'Promoted via run #'.$runId,
                        'performed_by_id' => $performedBy,
                    ]);
                }
            }

            // Automatically populate entry-level (lowest course, e.g. ECD A or Form 1) with new intake admissions for target year
            $lowestCourse = Course::withoutGlobalScopes()
                ->where('school_id', $run->school_id)
                ->orderBy('sequence_order')
                ->orderBy('id')
                ->first();

            if ($lowestCourse) {
                $lowestSection = Section::withoutGlobalScopes()
                    ->where('school_id', $run->school_id)
                    ->where('course_id', $lowestCourse->id)
                    ->orderBy('rank_order')
                    ->first();

                if ($lowestSection) {
                    $existingLowestCount = Enrollment::withoutGlobalScopes()
                        ->where('school_id', $run->school_id)
                        ->where('academic_year_id', $run->target_academic_year_id)
                        ->where('course_id', $lowestCourse->id)
                        ->count();

                    if ($existingLowestCount === 0) {
                        for ($i = 1; $i <= 10; $i++) {
                            $intakeIdNumber = static::uniqueIntakeStudentIdNumber($run->school_id);

                            $student = Student::create([
                                'school_id' => $run->school_id,
                                'student_id_number' => $intakeIdNumber,
                                'admission_number' => 'ADM-NEW-'.substr($intakeIdNumber, strrpos($intakeIdNumber, '-') + 1),
                                'first_name' => collect(['Kuda', 'Tariro', 'Tanaka', 'Farai', 'Ruvimbo', 'Chipo', 'Nyasha'])->random(),
                                'last_name' => collect(['Moyo', 'Sibanda', 'Ndlovu', 'Dube', 'Mutasa', 'Gumbo', 'Zhou'])->random(),
                                'gender' => collect(['male', 'female'])->random(),
                                'date_of_birth' => now()->subYears(5)->toDateString(),
                                'admission_date' => $now->toDateString(),
                                'status' => 'active',
                            ]);

                            Enrollment::create([
                                'school_id' => $run->school_id,
                                'student_id' => $student->id,
                                'academic_year_id' => $run->target_academic_year_id,
                                'course_id' => $lowestCourse->id,
                                'section_id' => $lowestSection->id,
                                'status' => Enrollment::STATUS_ACTIVE,
                                'effective_date' => $now,
                                'reason' => 'New entry-level admission intake',
                                'performed_by_id' => $performedBy,
                            ]);
                        }
                    }
                }
            }

            $run->update([
                'status' => PromotionRun::STATUS_COMMITTED,
                'committed_at' => $now,
            ]);
        });
    }

    public function undo(int $runId, ?int $performedBy = null): void
    {
        DB::transaction(function () use ($runId, $performedBy) {
            $run = PromotionRun::findOrFail($runId);

            if ($run->status !== PromotionRun::STATUS_COMMITTED) {
                throw new \RuntimeException("Run #{$runId} is not committed — only committed runs can be undone.");
            }

            $items = PromotionItem::where('promotion_run_id', $runId)
                ->where('decision', PromotionItem::DECISION_PROMOTED)
                ->get();

            $now = Carbon::now();

            foreach ($items as $item) {
                if ($item->target_course_id) {
                    Enrollment::where('school_id', $run->school_id)
                        ->where('student_id', $item->student_id)
                        ->where('academic_year_id', $run->target_academic_year_id)
                        ->where('course_id', $item->target_course_id)
                        ->delete();
                } else {
                    Student::withoutGlobalScopes()
                        ->whereKey($item->student_id)
                        ->update(['status' => 'active']);
                }

                $oldEnrollment = $item->sourceEnrollment;
                if ($oldEnrollment) {
                    $oldEnrollment->update([
                        'status' => Enrollment::STATUS_ACTIVE,
                        'effective_date' => $now,
                        'reason' => 'Promotion run #'.$runId.' undone',
                        'performed_by_id' => $performedBy,
                    ]);
                }
            }

            $run->update([
                'status' => PromotionRun::STATUS_DRAFT,
                'committed_at' => null,
            ]);
        });
    }

    /**
     * Resolve a concrete target section for a promoted student.
     *
     * Falls back to the first section of the target course, and as a last
     * resort auto-creates a default section named after the course so the
     * promotion never fails with a null section_id.
     */
    protected function resolveTargetSectionId(int $schoolId, int $courseId, ?int $sectionId): int
    {
        if ($sectionId) {
            return $sectionId;
        }

        $first = Section::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('course_id', $courseId)
            ->orderBy('rank_order')
            ->orderBy('id')
            ->first();

        if ($first) {
            return $first->id;
        }

        $course = Course::withoutGlobalScopes()->find($courseId);

        return Section::create([
            'school_id' => $schoolId,
            'course_id' => $courseId,
            'name' => $course?->name ?? "Course #{$courseId}",
            'rank_order' => 1,
        ])->id;
    }

    protected function uniqueIntakeStudentIdNumber(int $schoolId): string
    {
        do {
            $candidate = 'INTAKE-'.now()->year.'-'.sprintf('%03d', rand(100, 999));
        } while (Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', $candidate)
            ->exists());

        return $candidate;
    }

    protected function resolveParallelSection(
        int $schoolId,
        int $sourceCourseId,
        int $targetCourseId,
        ?string $sourceSectionName,
    ): ?int {
        if ($sourceSectionName && $sourceCourseId !== $targetCourseId) {
            $parallel = Section::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('course_id', $targetCourseId)
                ->where('name', $sourceSectionName)
                ->first();

            if ($parallel) {
                return $parallel->id;
            }
        }

        return Section::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('course_id', $targetCourseId)
            ->orderBy('rank_order')
            ->first()?->id;
    }
}
