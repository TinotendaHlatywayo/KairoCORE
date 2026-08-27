<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DigitalAssessment\Enums\QuestionDifficulty;
use Modules\DigitalAssessment\Models\AdaptiveAssessment;
use Modules\DigitalAssessment\Models\AdaptiveRule;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Models\QuestionBank;

class AdaptiveEngine
{
    public function getAdaptiveConfig(DigitalAssessment $assessment): ?AdaptiveAssessment
    {
        return AdaptiveAssessment::where('school_id', current_tenant()?->id)
            ->where('digital_assessment_id', $assessment->id)
            ->first();
    }

    public function getOrEnableAdaptiveConfig(DigitalAssessment $assessment): AdaptiveAssessment
    {
        $config = $this->getAdaptiveConfig($assessment);

        if (! $config) {
            $config = AdaptiveAssessment::create([
                'school_id' => current_tenant()?->id,
                'digital_assessment_id' => $assessment->id,
                'is_active' => true,
                'base_difficulty' => 50,
                'min_difficulty' => 10,
                'max_difficulty' => 90,
                'window_size' => 3,
                'adjustment_rate' => 10,
            ]);
        } else {
            $config->update(['is_active' => true]);
        }

        return $config;
    }

    public function disableAdaptive(DigitalAssessment $assessment): void
    {
        $config = $this->getAdaptiveConfig($assessment);
        if ($config) {
            $config->update(['is_active' => false]);
        }
    }

    public function selectNextQuestion(DigitalAssessment $assessment, DigitalAssessmentAttempt $attempt): ?QuestionBank
    {
        $config = $this->getAdaptiveConfig($assessment);

        if (! $config || ! $config->is_active) {
            return $this->selectRandomUnanswered($assessment, $attempt);
        }

        $currentDifficulty = $this->calculateCurrentDifficulty($config, $attempt);
        $answeredIds = $attempt->responses()->pluck('question_bank_id')->toArray();

        $questions = QuestionBank::where('school_id', current_tenant()?->id)
            ->where('subject_id', $assessment->subject_id)
            ->whereNotIn('id', $answeredIds)
            ->get();

        if ($questions->isEmpty()) {
            return null;
        }

        $rules = $config->rules()->orderBy('priority', 'desc')->get();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $currentDifficulty)) {
                $questions = $this->applyRuleToQuestions($questions, $rule);
                break;
            }
        }

        $questions = $this->sortByDifficulty($questions, $currentDifficulty);

        return $questions->first();
    }

    public function calculateCurrentDifficulty(AdaptiveAssessment $config, DigitalAssessmentAttempt $attempt): int
    {
        $recentResponses = $attempt->responses()
            ->whereNotNull('marks_awarded')
            ->orderBy('answered_at', 'desc')
            ->limit($config->window_size)
            ->get();

        if ($recentResponses->isEmpty()) {
            return $config->base_difficulty;
        }

        $scores = $recentResponses->map(function ($r) {
            $maxMarks = $r->question?->marks ?? 1;
            return $maxMarks > 0 ? ($r->marks_awarded / $maxMarks) * 100 : 0;
        });

        $avgScore = $scores->avg();
        $adjustment = ($avgScore - 50) * ($config->adjustment_rate / 50);
        $newDifficulty = $config->base_difficulty + $adjustment;

        return (int) max($config->min_difficulty, min($config->max_difficulty, $newDifficulty));
    }

    public function applyRulesAfterResponse(DigitalAssessmentAttempt $attempt, DigitalAssessmentResponse $response): void
    {
        $assessment = $attempt->assessment;
        $config = $this->getAdaptiveConfig($assessment);

        if (! $config || ! $config->is_active) {
            return;
        }

        $currentDifficulty = $this->calculateCurrentDifficulty($config, $attempt);
        $rules = $config->rules()->orderBy('priority', 'desc')->get();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $currentDifficulty)) {
                $this->applyRuleEffect($rule, $attempt);
            }
        }
    }

    protected function evaluateRule(AdaptiveRule $rule, int $currentDifficulty): bool
    {
        return match ($rule->rule_type) {
            'difficulty_change' => true,
            'score_threshold' => match ($rule->condition_op) {
                '>=' => $currentDifficulty >= $rule->threshold_from,
                '<=' => $currentDifficulty <= $rule->threshold_from,
                '=' => $currentDifficulty == $rule->threshold_from,
                '>' => $currentDifficulty > $rule->threshold_from,
                '<' => $currentDifficulty < $rule->threshold_from,
                default => false,
            },
            'streak' => $this->evaluateStreakRule($rule, $currentDifficulty),
            default => false,
        };
    }

    protected function evaluateStreakRule(AdaptiveRule $rule, int $currentDifficulty): bool
    {
        return true;
    }

    protected function applyRuleToQuestions(Collection $questions, AdaptiveRule $rule): Collection
    {
        if ($rule->rule_type === 'difficulty_change' && $rule->target_difficulty !== null) {
            $target = $rule->target_difficulty;
            return $questions->sortBy(function ($q) use ($target) {
                return abs($q->difficulty->value - $target);
            })->values();
        }

        if ($rule->target_question_bank_id) {
            $targetQ = $questions->where('id', $rule->target_question_bank_id)->first();
            if ($targetQ) {
                return $questions->prepend($targetQ)->values();
            }
        }

        return $questions;
    }

    protected function applyRuleEffect(AdaptiveRule $rule, DigitalAssessmentAttempt $attempt): void
    {
        if ($rule->rule_type === 'difficulty_change' && $rule->adjustment !== 0) {
            $config = $this->getAdaptiveConfig($attempt->assessment);
            if ($config) {
                $newBase = max(
                    $config->min_difficulty,
                    min($config->max_difficulty, $config->base_difficulty + $rule->adjustment)
                );
                $config->update(['base_difficulty' => $newBase]);
            }
        }
    }

    protected function sortByDifficulty(Collection $questions, int $targetDifficulty): Collection
    {
        return $questions->sortBy(function ($q) use ($targetDifficulty) {
            return abs($q->difficulty->value - $targetDifficulty);
        })->values();
    }

    protected function selectRandomUnanswered(DigitalAssessment $assessment, DigitalAssessmentAttempt $attempt): ?QuestionBank
    {
        $answeredIds = $attempt->responses()->pluck('question_bank_id')->toArray();

        return QuestionBank::where('school_id', current_tenant()?->id)
            ->where('subject_id', $assessment->subject_id)
            ->whereNotIn('id', $answeredIds)
            ->inRandomOrder()
            ->first();
    }

    public function createRule(array $data): AdaptiveRule
    {
        return AdaptiveRule::create($data);
    }

    public function updateRule(AdaptiveRule $rule, array $data): AdaptiveRule
    {
        $rule->update($data);

        return $rule->fresh();
    }

    public function deleteRule(AdaptiveRule $rule): bool
    {
        return $rule->delete();
    }
}
