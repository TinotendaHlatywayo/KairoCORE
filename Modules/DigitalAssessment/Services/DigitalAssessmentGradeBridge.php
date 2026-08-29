<?php

namespace Modules\DigitalAssessment\Services;

use Modules\Academics\Models\AssessmentMark;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\Students\Models\Enrollment;

/**
 * Bridges outcomes from the digital assessment engine into the report-card
 * grading pipeline (assessment_marks). Only assessments that are configured to
 * contribute to a grade and linked to an assessment type are synced.
 */
class DigitalAssessmentGradeBridge
{
    public function syncAttemptGrade(DigitalAssessmentAttempt $attempt): void
    {
        $assessment = $attempt->assessment;

        if (! $assessment
            || ! $assessment->contributes_to_grade
            || ! $assessment->assessment_type_id) {
            return;
        }

        $marksObtained = (float) $attempt->marks_obtained;
        $totalMarks = (float) ($attempt->max_possible_marks ?: $assessment->getCalculatedTotalMarks());
        $maxMark = (float) ($assessment->assessmentType?->max_mark ?? 0);

        if ($maxMark <= 0) {
            return;
        }

        $enrollment = $this->resolveEnrollment($attempt, $assessment);
        if (! $enrollment || ! $assessment->subject_id) {
            return;
        }

        // Scale the raw digital score onto the assessment type's max mark so the
        // report card (which normalises marks_obtained / max_mark) reads correctly.
        $scaled = $totalMarks > 0 ? round(($marksObtained / $totalMarks) * $maxMark, 2) : 0.00;

        $attributes = [
            'school_id' => $assessment->school_id,
            'enrollment_id' => $enrollment->id,
            'assessment_type_id' => $assessment->assessment_type_id,
            'subject_id' => $assessment->subject_id,
        ];

        $values = ['marks_obtained' => $scaled];

        AssessmentMark::updateOrCreate($attributes, $values);
    }

    protected function resolveEnrollment(DigitalAssessmentAttempt $attempt, $assessment): ?Enrollment
    {
        if ($attempt->enrollment_id) {
            $enrollment = Enrollment::find($attempt->enrollment_id);

            if ($enrollment) {
                return $enrollment;
            }
        }

        // Fall back to the student's enrollment in the assessment's section and
        // academic year. Enrollments are per student/section/academic year; the
        // term is implied by the assessment type configuration.
        $query = Enrollment::where('school_id', $assessment->school_id)
            ->where('student_id', $attempt->student_id);

        if ($assessment->section_id) {
            $query->where('section_id', $assessment->section_id);
        }

        if ($assessment->academic_year_id) {
            $query->where('academic_year_id', $assessment->academic_year_id);
        }

        return $query->first();
    }
}
