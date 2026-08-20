<?php

namespace Modules\Academics\Services;

use Modules\Academics\Models\ExamMark;
use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\SbpAssessment;

class ResultEngine
{
    /**
     * Calculates Final Mark: (Exam Papers sum weighted to 80%) + (SBP Project 20%)
     */
    public function calculateSubjectFinal($studentId, $subjectId, $examId, $termId)
    {
        // 1. Get Exam Marks for this subject
        $marks = ExamMark::where('student_id', $studentId)
            ->whereHas('examPaper', function ($q) use ($subjectId, $examId) {
                $q->where('subject_id', $subjectId)->where('exam_id', $examId);
            })->get();

        $examWeighted = 0;
        foreach ($marks as $mark) {
            $paper = $mark->examPaper;
            // Formula: (Mark / Max) * Weight
            $examWeighted += ($mark->marks_obtained / $paper->max_mark) * $paper->weight_percentage;
        }

        // 2. Add SBP (School Based Project) score
        $sbp = SbpAssessment::where(['student_id' => $studentId, 'subject_id' => $subjectId, 'term_id' => $termId])->first();
        $sbpScore = $sbp ? $sbp->score : 0;

        return round($examWeighted + $sbpScore, 2);
    }

    public function getGrade($schoolId, $score)
    {
        return GradingPoint::whereHas('gradingScale', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first()?->symbol ?? 'U';
    }
}
