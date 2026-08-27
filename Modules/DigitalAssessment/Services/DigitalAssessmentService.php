<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentQuestion;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\DigitalAssessment\Models\AdaptiveAssessment;

class DigitalAssessmentService
{
    public function createAssessment(array $data): DigitalAssessment
    {
        $data['school_id'] = current_tenant()?->id ?? auth()->user()->school_id;
        $data['created_by_id'] = auth()->id();

        if (! isset($data['status'])) {
            $data['status'] = AssessmentStatus::Draft;
        }

        $assessment = DigitalAssessment::create($data);

        $this->syncAdaptiveConfig($assessment, $data);

        return $assessment;
    }

    public function syncAdaptiveConfig(DigitalAssessment $assessment, array $data): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()->school_id;

        if (! empty($data['adaptive_mode'])) {
            AdaptiveAssessment::updateOrCreate(
                ['school_id' => $schoolId, 'digital_assessment_id' => $assessment->id],
                [
                    'is_active' => true,
                    'base_difficulty' => $data['adaptive_base_difficulty'] ?? 50,
                    'min_difficulty' => 5,
                    'max_difficulty' => 95,
                    'window_size' => $data['adaptive_window_size'] ?? 3,
                    'adjustment_rate' => $data['adaptive_adjustment_rate'] ?? 10,
                ]
            );
        } else {
            AdaptiveAssessment::where('school_id', $schoolId)
                ->where('digital_assessment_id', $assessment->id)
                ->update(['is_active' => false]);
        }
    }

    public function publishAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        if ($assessment->questions()->count() === 0) {
            throw new \DomainException('Cannot publish an assessment with no questions attached.');
        }

        $assessment->update([
            'status' => AssessmentStatus::Published,
            'published_at' => now(),
            'total_marks' => $assessment->getCalculatedTotalMarks(),
        ]);

        return $assessment->fresh();
    }

    public function activateAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        $assessment->update(['status' => AssessmentStatus::Active]);

        return $assessment->fresh();
    }

    public function closeAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        $assessment->update(['status' => AssessmentStatus::Closed]);

        return $assessment->fresh();
    }

    public function archiveAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        $assessment->update(['status' => AssessmentStatus::Archived]);

        return $assessment->fresh();
    }

    public function scheduleAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        if (! $assessment->availability_start_at) {
            throw new \DomainException('Set an availability start date before scheduling.');
        }

        $assessment->update(['status' => AssessmentStatus::Scheduled]);

        return $assessment->fresh();
    }

    public function addQuestion(DigitalAssessment $assessment, QuestionBank $question, ?int $order = null, ?float $marksOverride = null): DigitalAssessmentQuestion
    {
        $existing = $assessment->questions()
            ->where('question_bank_id', $question->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $maxOrder = $assessment->questions()->max('question_order') ?? 0;

        return DigitalAssessmentQuestion::create([
            'digital_assessment_id' => $assessment->id,
            'question_bank_id' => $question->id,
            'question_order' => $order ?? ($maxOrder + 1),
            'marks_override' => $marksOverride,
        ]);
    }

    public function removeQuestion(DigitalAssessment $assessment, int $questionBankId): bool
    {
        return $assessment->questions()
            ->where('question_bank_id', $questionBankId)
            ->delete() > 0;
    }

    public function reorderQuestions(DigitalAssessment $assessment, array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            DigitalAssessmentQuestion::where('id', $id)
                ->update(['question_order' => $position + 1]);
        }
    }

    public function attachQuestions(DigitalAssessment $assessment, array $questionIds): void
    {
        $maxOrder = $assessment->questions()->max('question_order') ?? 0;

        foreach ($questionIds as $i => $qId) {
            $exists = $assessment->questions()
                ->where('question_bank_id', $qId)
                ->exists();

            if (! $exists) {
                DigitalAssessmentQuestion::create([
                    'digital_assessment_id' => $assessment->id,
                    'question_bank_id' => $qId,
                    'question_order' => $maxOrder + $i + 1,
                ]);
            }
        }
    }

    public function duplicateAssessment(DigitalAssessment $assessment): DigitalAssessment
    {
        $data = $assessment->toArray();

        unset(
            $data['id'],
            $data['status'],
            $data['published_at'],
            $data['created_at'],
            $data['updated_at']
        );

        $data['title'] = $data['title'] . ' (Copy)';
        $data['status'] = AssessmentStatus::Draft;
        $data['created_by_id'] = auth()->id();

        $new = DigitalAssessment::create($data);

        foreach ($assessment->questions as $aq) {
            DigitalAssessmentQuestion::create([
                'digital_assessment_id' => $new->id,
                'question_bank_id' => $aq->question_bank_id,
                'question_order' => $aq->question_order,
                'marks_override' => $aq->marks_override,
            ]);
        }

        return $new;
    }

    public function getAssessmentStats(DigitalAssessment $assessment): array
    {
        return [
            'total_questions' => $assessment->questions()->count(),
            'total_attempts' => $assessment->attempts()->count(),
            'completed_attempts' => $assessment->attempts()->complete()->count(),
            'in_progress_attempts' => $assessment->attempts()->inProgress()->count(),
            'calculated_marks' => $assessment->getCalculatedTotalMarks(),
        ];
    }

    public function recalculateTotalMarks(DigitalAssessment $assessment): float
    {
        $total = $assessment->getCalculatedTotalMarks();

        $assessment->update(['total_marks' => $total]);

        return $total;
    }
}
