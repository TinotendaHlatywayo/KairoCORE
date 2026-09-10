<?php

namespace App\Services\Promotion;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Promotion\Models\PromotionItem;
use Modules\Promotion\Models\PromotionRun;
use Modules\Students\Models\Enrollment;

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

            $courseLevelCache = [];

            foreach ($activeEnrollments as $enrollment) {
                $studentId = $enrollment->student_id;
                $sourceCourse = $enrollment->course;

                if (! $sourceCourse) {
                    continue;
                }

                $nextCourseId = $sourceCourse->next_level_id ?? $sourceCourse->id;
                $isTerminal = (bool) $sourceCourse->is_terminal;

                $decision = $isTerminal
                    ? PromotionItem::DECISION_NEEDS_SCREENING
                    : PromotionItem::DECISION_PROMOTED;

                $targetSectionId = null;

                if ($decision === PromotionItem::DECISION_PROMOTED) {
                    $targetSectionId = $this->resolveParallelSection(
                        $schoolId,
                        $sourceCourse->id,
                        $nextCourseId,
                        $enrollment->section->name ?? null,
                    );
                }

                $decisionReason = match ($decision) {
                    PromotionItem::DECISION_PROMOTED => 'Auto-promoted via level progression',
                    PromotionItem::DECISION_NEEDS_SCREENING => 'Terminal level — requires screening',
                    default => null,
                };

                PromotionItem::create([
                    'school_id' => $schoolId,
                    'promotion_run_id' => $run->id,
                    'student_id' => $studentId,
                    'source_enrollment_id' => $enrollment->id,
                    'decision' => $decision,
                    'target_course_id' => $nextCourseId,
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
                        'reason' => $item->reason ?? 'Promoted in run #' . $runId,
                        'performed_by_id' => $performedBy,
                    ]);
                }

                Enrollment::create([
                    'school_id' => $run->school_id,
                    'student_id' => $item->student_id,
                    'academic_year_id' => $run->target_academic_year_id,
                    'course_id' => $item->target_course_id,
                    'section_id' => $item->target_section_id,
                    'roll_number' => $oldEnrollment?->roll_number,
                    'term_id' => $oldEnrollment?->term_id,
                    'status' => Enrollment::STATUS_ACTIVE,
                    'effective_date' => $now,
                    'reason' => $item->reason ?? 'Promoted via run #' . $runId,
                    'performed_by_id' => $performedBy,
                ]);
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
                Enrollment::where('school_id', $run->school_id)
                    ->where('student_id', $item->student_id)
                    ->where('academic_year_id', $run->target_academic_year_id)
                    ->where('course_id', $item->target_course_id)
                    ->delete();

                $oldEnrollment = $item->sourceEnrollment;
                if ($oldEnrollment) {
                    $oldEnrollment->update([
                        'status' => Enrollment::STATUS_ACTIVE,
                        'effective_date' => $now,
                        'reason' => 'Promotion run #' . $runId . ' undone',
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
