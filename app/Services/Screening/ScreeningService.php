<?php

namespace App\Services\Screening;

use Modules\Students\Models\ScreeningRule;
use Modules\Screening\Models\ScreeningItem;
use Modules\Screening\Models\ScreeningRun;

class ScreeningService
{
    public function __construct(
        protected ScreeningScoreService $scoreService,
    ) {
    }

    public function preview(
        int $schoolId,
        int $sourceYearId,
        int $targetYearId,
        ?int $promotionRunId = null,
        ?int $createdBy = null,
    ): ScreeningRun {
        return \DB::transaction(function () use ($schoolId, $sourceYearId, $targetYearId, $promotionRunId, $createdBy) {
            $run = ScreeningRun::create([
                'school_id' => $schoolId,
                'source_academic_year_id' => $sourceYearId,
                'target_academic_year_id' => $targetYearId,
                'promotion_run_id' => $promotionRunId,
                'status' => ScreeningRun::STATUS_DRAFT,
                'created_by_id' => $createdBy,
            ]);

            $candidates = $this->resolveCandidates($schoolId, $sourceYearId, $promotionRunId);

            foreach ($candidates as $enrollment) {
                $profile = $this->scoreService->studentScreeningProfile($enrollment->student_id, $sourceYearId);
                $overallScore = $profile['overall_score'];

                $placement = $this->resolvePlacement(
                    $schoolId,
                    $enrollment->course_id,
                    $profile,
                );

                ScreeningItem::create([
                    'school_id' => $schoolId,
                    'screening_run_id' => $run->id,
                    'student_id' => $enrollment->student_id,
                    'source_enrollment_id' => $enrollment->id,
                    'overall_score' => $overallScore,
                    'decision' => $placement['decision'],
                    'target_course_id' => $placement['target_course_id'],
                    'target_section_id' => $placement['target_section_id'],
                    'reason' => $placement['reason'],
                ]);
            }

            return $run;
        });
    }

    public function commit(int $runId, ?int $performedBy = null): void
    {
        \DB::transaction(function () use ($runId, $performedBy) {
            $run = ScreeningRun::findOrFail($runId);

            if ($run->status !== ScreeningRun::STATUS_DRAFT) {
                throw new \RuntimeException("Screening run #{$runId} is in status '{$run->status}' — only draft runs can be committed.");
            }

            $run->update(['status' => ScreeningRun::STATUS_IN_PROGRESS]);

            $items = ScreeningItem::where('screening_run_id', $runId)
                ->where('decision', ScreeningItem::DECISION_PLACED)
                ->get();

            $now = \Illuminate\Support\Carbon::now();

            foreach ($items as $item) {
                $oldEnrollment = $item->sourceEnrollment;

                if ($oldEnrollment) {
                    $oldEnrollment->update([
                        'status' => \Modules\Students\Models\Enrollment::STATUS_PROMOTED,
                        'effective_date' => $now,
                        'reason' => $item->reason ?? 'Placed via screening run #' . $runId,
                        'performed_by_id' => $performedBy,
                    ]);
                }

                \Modules\Students\Models\Enrollment::create([
                    'school_id' => $run->school_id,
                    'student_id' => $item->student_id,
                    'academic_year_id' => $run->target_academic_year_id,
                    'course_id' => $item->target_course_id,
                    'section_id' => $item->target_section_id,
                    'roll_number' => $oldEnrollment?->roll_number,
                    'term_id' => $oldEnrollment?->term_id,
                    'status' => \Modules\Students\Models\Enrollment::STATUS_ACTIVE,
                    'effective_date' => $now,
                    'reason' => $item->reason ?? 'Placed via screening run #' . $runId,
                    'performed_by_id' => $performedBy,
                ]);
            }

            $run->update([
                'status' => ScreeningRun::STATUS_COMMITTED,
                'committed_at' => $now,
            ]);
        });
    }

    protected function resolveCandidates(int $schoolId, int $sourceYearId, ?int $promotionRunId): \Illuminate\Support\Collection
    {
        if ($promotionRunId) {
            return \Modules\Students\Models\Enrollment::withoutGlobalScopes()
                ->whereIn('id', function ($q) use ($promotionRunId) {
                    $q->select('source_enrollment_id')
                        ->from('promotion_items')
                        ->where('promotion_run_id', $promotionRunId)
                        ->where('decision', 'needs_screening');
                })
                ->get();
        }

        return \Modules\Students\Models\Enrollment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $sourceYearId)
            ->where('status', \Modules\Students\Models\Enrollment::STATUS_ACTIVE)
            ->whereHas('course', function ($q) {
                $q->where('is_terminal', true);
            })
            ->get();
    }

    protected function resolvePlacement(int $schoolId, int $sourceCourseId, array $profile): array
    {
        $rules = ScreeningRule::query()
            ->where('school_id', $schoolId)
            ->where('source_course_id', $sourceCourseId)
            ->orderByDesc('min_percentage')
            ->get();

        foreach ($rules as $rule) {
            $score = match ($rule->rule_type) {
                'subject' => $profile['subject_scores'][$rule->subject_id]['score'] ?? null,
                default => $profile['overall_score'],
            };

            if ($score === null) {
                continue;
            }

            if ($score >= (float) $rule->min_percentage) {
                return [
                    'decision' => ScreeningItem::DECISION_PLACED,
                    'target_course_id' => $rule->target_course_id ?? $sourceCourseId,
                    'target_section_id' => $rule->target_section_id,
                    'reason' => "Matched rule '{$rule->rule_type}' threshold {$rule->min_percentage}% with score {$score}%",
                ];
            }
        }

        return [
            'decision' => ScreeningItem::DECISION_UNPLACED,
            'target_course_id' => $sourceCourseId,
            'target_section_id' => null,
            'reason' => 'No screening rule matched for available scores',
        ];
    }
}