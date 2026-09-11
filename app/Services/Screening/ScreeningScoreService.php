<?php

namespace App\Services\Screening;

use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Subject;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class ScreeningScoreService
{
    /**
     * Level 1: combine an assessment mark into a percentage of its
     * assessment type's max_mark.
     */
    public function percentage(?float $marksObtained, AssessmentType $assessmentType): ?float
    {
        if ($marksObtained === null || $assessmentType->max_mark <= 0) {
            return null;
        }

        return (float) ($marksObtained / $assessmentType->max_mark) * 100;
    }

    /**
     * Level 1 averaging: weighted percentage for a single subject across
     * all of a student's assessment marks.
     *
     * Returns null when no usable marks exist.
     */
    public function subjectScore(int $enrollmentId, int $subjectId): ?float
    {
        $marks = AssessmentMark::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('subject_id', $subjectId)
            ->with('assessmentType')
            ->get();

        $weightedPct = 0.0;
        $weightTotal = 0.0;

        foreach ($marks as $mark) {
            $type = $mark->assessmentType;

            if (! $type || $type->max_mark <= 0 || $type->weight_percentage <= 0) {
                continue;
            }

            $pct = $this->percentage($mark->marks_obtained, $type);

            if ($pct === null) {
                continue;
            }

            $weightedPct += $pct * $type->weight_percentage;
            $weightTotal += $type->weight_percentage;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return round($weightedPct / $weightTotal, 2);
    }

    /**
     * Level 2 averaging: overall screening score for a student in a
     * given academic year. Arithmetic mean of per-subject scores across
     * all subjects found in the student's assessment marks.
     */
    public function overallScore(int $studentId, int $academicYearId): ?float
    {
        $enrollmentId = $this->resolveEnrollmentId($studentId, $academicYearId);

        if (! $enrollmentId) {
            return null;
        }

        $subjectIds = AssessmentMark::query()
            ->where('enrollment_id', $enrollmentId)
            ->distinct()
            ->pluck('subject_id');

        if ($subjectIds->isEmpty()) {
            return null;
        }

        $scores = $subjectIds->map(fn (int $subjectId) => $this->subjectScore($enrollmentId, $subjectId))
            ->filter(fn (?float $score) => $score !== null);

        if ($scores->isEmpty()) {
            return null;
        }

        return round($scores->avg(), 2);
    }

    /**
     * Rich result: per-subject scores + overall score.
     *
     * @param  array  $criteria  Optional screening criteria:
     *                           [
     *                               'score_basis' => 'overall'|'subjects',
     *                               'subject_ids' => int[],
     *                               'term_ids' => int[],
     *                               'academic_year_ids' => int[],
     *                           ]
     */
    public function studentScreeningProfile(int $studentId, int $academicYearId, array $criteria = []): array
    {
        $includedYears = array_values(array_filter(array_map('intval', $criteria['academic_year_ids'] ?? [])))
            ?: [$academicYearId];

        $enrollmentIds = Enrollment::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->whereIn('academic_year_id', $includedYears)
            ->pluck('id');

        if ($enrollmentIds->isEmpty()) {
            return [
                'enrollment_id' => null,
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'score_basis' => ($criteria['score_basis'] ?? 'overall') === 'subjects' ? 'subjects' : 'overall',
                'subject_scores' => collect(),
                'overall_score' => null,
            ];
        }

        $termIds = array_values(array_filter(array_map('intval', $criteria['term_ids'] ?? [])));
        $basis = ($criteria['score_basis'] ?? 'overall') === 'subjects' ? 'subjects' : 'overall';
        $subjectIds = $basis === 'subjects'
            ? array_values(array_filter(array_map('intval', $criteria['subject_ids'] ?? [])))
            : [];

        $marks = AssessmentMark::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->with('assessmentType');

        if ($basis === 'subjects' && $subjectIds) {
            $marks->whereIn('subject_id', $subjectIds);
        }

        if ($termIds) {
            $marks->whereHas('assessmentType', function ($q) use ($termIds) {
                $q->whereIn('term_id', $termIds);
            });
        }

        $subjectScores = $this->subjectScoresFromMarks($marks->get());

        $scores = $subjectScores->pluck('score')->filter(fn (?float $score) => $score !== null);

        return [
            'enrollment_id' => $enrollmentIds->first(),
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'score_basis' => $basis,
            'subject_scores' => $subjectScores,
            'overall_score' => $scores->isEmpty() ? null : round($scores->avg(), 2),
        ];
    }

    /**
     * Build per-subject weighted scores from a pooled set of assessment marks.
     */
    protected function subjectScoresFromMarks(Collection $marks): Collection
    {
        return $marks->groupBy('subject_id')->mapWithKeys(function ($subjectMarks, $subjectId) {
            $subject = Subject::find((int) $subjectId);

            $weightedPct = 0.0;
            $weightTotal = 0.0;

            foreach ($subjectMarks as $mark) {
                $type = $mark->assessmentType;

                if (! $type || $type->max_mark <= 0 || $type->weight_percentage <= 0) {
                    continue;
                }

                $pct = $this->percentage($mark->marks_obtained, $type);

                if ($pct === null) {
                    continue;
                }

                $weightedPct += $pct * $type->weight_percentage;
                $weightTotal += $type->weight_percentage;
            }

            return [
                (int) $subjectId => [
                    'subject_id' => (int) $subjectId,
                    'subject_name' => $subject?->name ?? "Subject #{$subjectId}",
                    'score' => $weightTotal <= 0 ? null : round($weightedPct / $weightTotal, 2),
                ],
            ];
        });
    }

    /**
     * Resolve the active (or any) enrollment id for a student+year.
     */
    protected function resolveEnrollmentId(int $studentId, int $academicYearId): ?int
    {
        $enrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->orderByDesc('id')
            ->first();

        return $enrollment?->id;
    }

    /**
     * Convenience: overall score for a whole section/cohort, keyed by student_id.
     */
    public function cohortScores(Collection $students, int $academicYearId): Collection
    {
        return $students->mapWithKeys(function ($student) use ($academicYearId) {
            $studentId = $student instanceof Student ? $student->id : $student;

            return [$studentId => $this->overallScore($studentId, $academicYearId)];
        })->filter(fn (?float $score) => $score !== null);
    }
}