<?php

namespace Modules\Academics\Services;

use Modules\Academics\Models\AssessmentMark; // FIXED: Targets active table
use Modules\Academics\Models\AssessmentType; // FIXED: Targets active table
use Modules\Students\Models\Enrollment;

class AssessmentResultCalculator
{
    /**
     * Calculates the overall final subject score (out of 100%) for a student
     * based on their recorded assessment_marks.
     */
    public function calculateSubjectFinal($enrollmentId, $subjectId, $termId)
    {
        $enrollment = Enrollment::with('section.course')->find($enrollmentId);
        if (! $enrollment) {
            return 0.00;
        }

        $schoolId = $enrollment->school_id;

        // Find all active assessment types configured for this subject/course/section in this term
        // FIXED: Allows marking, submitted, reviewed, and locked states to calculate dynamically for previews
        $assessments = AssessmentType::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->whereIn('status', ['marking', 'submitted', 'reviewed', 'locked', 'published'])
            ->where(function ($q) use ($enrollment) {
                $q->whereNull('course_id')->orWhere('course_id', $enrollment->section?->course_id);
            })
            ->where(function ($q) use ($enrollment) {
                $q->whereNull('section_id')->orWhere('section_id', $enrollment->section_id);
            })
            ->where(function ($q) use ($subjectId) {
                $q->whereNull('subject_id')->orWhere('subject_id', $subjectId);
            })
            ->get();

        if ($assessments->isEmpty()) {
            return 0.00;
        }

        $weightedSum = 0.00;
        $totalWeightUsed = 0.00;
        $hasAnyScore = false;

        foreach ($assessments as $assessment) {
            // Find student's mark for this specific assessment type
            $mark = AssessmentMark::where([
                'enrollment_id' => $enrollment->id,
                'assessment_type_id' => $assessment->id,
                'subject_id' => $subjectId,
            ])->first();

            if ($mark && ! is_null($mark->marks_obtained) && $assessment->max_mark > 0) {
                // Calculate percentage: (marks_obtained / max_mark) * 100
                $scorePercentage = ($mark->marks_obtained / $assessment->max_mark) * 100;
                $weightedContribution = $scorePercentage * ($assessment->weight_percentage / 100);

                $weightedSum += $weightedContribution;
                $totalWeightUsed += $assessment->weight_percentage;
                $hasAnyScore = true;
            }
        }

        return $hasAnyScore ? round($weightedSum, 2) : 0.00;
    }
}
