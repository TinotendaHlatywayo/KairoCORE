<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\StudentCompetency;
use Modules\Academics\Models\Subject;
use Modules\Students\Models\Enrollment;

class AcademicReportPdfController extends Controller
{
    /**
     * Entrypoint for single academic report streams
     */
    public function generate(Request $request, $id)
    {
        return $this->compileAndStreamReports([$id]);
    }

    /**
     * Entrypoint for batch/bulk academic report streams
     */
    public function bulkGenerate(Request $request)
    {
        $idsString = $request->query('ids');
        if (empty($idsString)) {
            return redirect()->back()->with('error', 'No report cards selected.');
        }

        $ids = explode(',', $idsString);

        return $this->compileAndStreamReports($ids);
    }

    /**
     * Centralized compilation pipeline for generating and streaming student reports safely
     */
    protected function compileAndStreamReports(array $ids)
    {
        $school = app('current_tenant');
        $schoolId = $school->id;

        // Fetch School-Wide Active Fallback Layout
        $activeDefaultTemplate = ReportTemplate::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        // Qualitative-to-Quantitative Unhu scale mapping
        $unhuScoreMap = [
            'excellent' => 100,
            'outstanding' => 100,
            'very_good' => 80,
            'satisfactory' => 60,
            'needs_improvement' => 40,
            'poor' => 20,
            'unsatisfactory' => 20,
        ];

        $reportsCompiled = [];

        foreach ($ids as $id) {
            // Load student record even if soft-deleted
            $report = AcademicReport::with([
                'student' => fn ($q) => $q->withTrashed(),
                'term.academicYear',
                'section.course',
            ])->find($id);

            if (! $report) {
                continue;
            }

            $student = $report->student;
            $term = $report->term;
            $year = $term?->academicYear;
            $course = $report->section?->course;
            $section = $report->section;
            $level = $course?->level ?? 'primary';

            // =========================================================================
            // SMART TEMPLATE RESOLVER (PRECEDENCE: Section -> Course -> Level -> Default)
            // =========================================================================
            $templateForCard = null;

            if ($section) {
                $templateForCard = ReportTemplate::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->where('scope_type', 'section')
                    ->where('section_id', $section->id)
                    ->first();
            }

            if (! $templateForCard && $course) {
                $templateForCard = ReportTemplate::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->where('scope_type', 'course')
                    ->where('course_id', $course->id)
                    ->first();
            }

            if (! $templateForCard) {
                $templateForCard = ReportTemplate::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->where('scope_type', 'level')
                    ->where('target_level', $level)
                    ->first();
            }

            if (! $templateForCard) {
                $templateForCard = $activeDefaultTemplate;
            }

            // Ultimate fallback layout config if no templates are configured in DB yet
            if (! $templateForCard) {
                $templateForCard = new ReportTemplate([
                    'name' => 'Default Classic Layout',
                    'design_theme' => 'classic_line',
                    'target_level' => 'all',
                    'layout_config' => [
                        'font_family' => 'Helvetica, sans-serif',
                        'header_font_size' => 20,
                        'header_color' => '#1e3a8a',
                        'body_text_color' => '#1e293b',
                        'table_header_bg' => '#f1f5f9',
                        'show_school_logo' => true,
                        'show_school_motto' => true,
                        'show_phone' => true,
                        'show_email' => true,
                        'show_address' => true,
                        'show_student_photo' => true,
                        'show_ranking' => true,
                        'show_next_term_fees' => true,
                        'show_ubuntu_competencies' => true,
                        'show_ubuntu_percentage' => true,
                        'show_grading_keys' => true,
                        'page_margin_v' => 12,
                        'page_margin_h' => 15,
                        'table_padding' => 5,
                        'page_border_width' => 0,
                    ],
                ]);
            }

            // Resolve Student Enrollment
            $enrollment = null;
            if ($student && $year) {
                $enrollment = Enrollment::where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->first();
            }

            $compiledSubjects = [];
            $competencies = [];

            // =========================================================================
            // PEER RANKING ENGINE (Class Position & Stream Position)
            // =========================================================================
            $classRank = null;
            $classTotal = 0;
            $streamRank = null;
            $streamTotal = 0;

            if ($section && $term && $student) {
                $classReports = AcademicReport::where('section_id', $section->id)
                    ->where('term_id', $term->id)
                    ->orderBy('overall_score', 'desc')
                    ->pluck('student_id')
                    ->toArray();

                $classRank = array_search($student->id, $classReports) !== false ? array_search($student->id, $classReports) + 1 : null;
                $classTotal = count($classReports);
            }

            if ($course && $term && $student) {
                $streamReports = AcademicReport::whereHas('section', fn ($q) => $q->where('course_id', $course->id))
                    ->where('term_id', $term->id)
                    ->orderBy('overall_score', 'desc')
                    ->pluck('student_id')
                    ->toArray();

                $streamRank = array_search($student->id, $streamReports) !== false ? array_search($student->id, $streamReports) + 1 : null;
                $streamTotal = count($streamReports);
            }

            // Compile dynamic academic subjects metrics
            if ($enrollment && $section && $course && $year) {
                // Get peer enrollment sets to evaluate averages safely
                $sectionEnrollmentIds = Enrollment::where('section_id', $section->id)
                    ->where('academic_year_id', $year->id)
                    ->pluck('id')
                    ->toArray();

                $streamEnrollmentIds = Enrollment::whereHas('section', fn ($q) => $q->where('course_id', $course->id))
                    ->where('academic_year_id', $year->id)
                    ->pluck('id')
                    ->toArray();

                // Fetch all subjects taught
                $subjects = Subject::where('school_id', $schoolId)->get();
                $includedAssessmentIds = $templateForCard->layout_config['included_assessments'] ?? [];

                foreach ($subjects as $subject) {
                    // FIXED: Checks active assessment_marks table directly using subject_id
                    $hasMarks = AssessmentMark::where('enrollment_id', $enrollment->id)
                        ->where('subject_id', $subject->id)
                        ->exists();

                    if (! $hasMarks) {
                        continue;
                    }

                    // FIXED: Calculates Class Average directly from active assessment_marks
                    $classAvg = AssessmentMark::where('subject_id', $subject->id)
                        ->whereIn('enrollment_id', $sectionEnrollmentIds)
                        ->avg('marks_obtained');

                    // FIXED: Calculates Stream Average directly from active assessment_marks
                    $streamAvg = AssessmentMark::where('subject_id', $subject->id)
                        ->whereIn('enrollment_id', $streamEnrollmentIds)
                        ->avg('marks_obtained');

                    // FIXED: Calculates Subject Rank directly from active assessment_marks
                    $subjectRanks = AssessmentMark::where('subject_id', $subject->id)
                        ->whereIn('enrollment_id', $sectionEnrollmentIds)
                        ->orderBy('marks_obtained', 'desc')
                        ->pluck('enrollment_id')
                        ->toArray();

                    $subjectRank = array_search($enrollment->id, $subjectRanks) !== false
                        ? array_search($enrollment->id, $subjectRanks) + 1
                        : null;

                    // =================================================================
                    // DYNAMIC WEIGHTED MARKS CALCULATOR (Based on template selections)
                    // =================================================================
                    $weightedSum = 0.00;
                    $totalWeightUsed = 0.00;
                    $hasAnyScore = false;

                    foreach ($includedAssessmentIds as $assessmentId) {
                        $markRecord = AssessmentMark::with('assessmentType')
                            ->where('enrollment_id', $enrollment->id)
                            ->where('subject_id', $subject->id)
                            ->where('assessment_type_id', $assessmentId)
                            ->first();

                        if ($markRecord && ! is_null($markRecord->marks_obtained)) {
                            $type = $markRecord->assessmentType;
                            if ($type && $type->max_mark > 0) {
                                $scorePercentage = ($markRecord->marks_obtained / $type->max_mark) * 100;
                                $weightedContribution = $scorePercentage * ($type->weight_percentage / 100);

                                $weightedSum += $weightedContribution;
                                $totalWeightUsed += $type->weight_percentage;
                                $hasAnyScore = true;
                            }
                        }
                    }

                    $finalMarkVal = $hasAnyScore ? round($weightedSum, 1) : null;

                    $compiledSubjects[] = [
                        'subject_code' => $subject->code,
                        'subject_name' => $subject->name,
                        'final_mark' => $finalMarkVal,
                        'class_avg' => $classAvg,
                        'stream_avg' => $streamAvg,
                        'subject_rank' => $subjectRank,
                        'initials' => 'TR',
                    ];
                }

                if ($level === 'primary') {
                    $competencies = StudentCompetency::where('enrollment_id', $enrollment->id)->get();
                }
            }

            // =========================================================================
            // UNHU/UBUNTU COMPETENCIES METADATA SANITIZER & SCORING AVERAGE
            // =========================================================================
            $unhuCompiled = [];
            $unhuRaw = $report->unhu_competencies ?? [];
            $displayedTraits = $templateForCard->layout_config['displayed_ubuntu_traits'] ?? [];

            $totalUnhuScore = 0;
            $unhuCount = 0;

            if (is_array($unhuRaw)) {
                foreach ($unhuRaw as $trait => $rating) {
                    if ($trait === 'outstanding_achievements') {
                        continue;
                    }

                    // Skip if the trait is not enabled/checked in template settings
                    if (! in_array($trait, $displayedTraits)) {
                        continue;
                    }

                    // Skip empty or unfilled ratings (Skipping empty profiles completely)
                    if (empty($rating) || trim($rating) === '') {
                        continue;
                    }

                    // Clean formatting: Remove dashes/underscores from traits and ratings
                    $cleanTrait = ucwords(str_replace('_', ' ', $trait));
                    $cleanRating = ucwords(str_replace('_', ' ', $rating));

                    $unhuCompiled[] = [
                        'trait' => $cleanTrait,
                        'rating' => $cleanRating,
                    ];

                    // Accumulate for percentage score calculation
                    $ratingKey = strtolower(str_replace(' ', '_', $rating));
                    if (isset($unhuScoreMap[$ratingKey])) {
                        $totalUnhuScore += $unhuScoreMap[$ratingKey];
                        $unhuCount++;
                    }
                }
            }

            $overallUnhuPercentage = $unhuCount > 0 ? round($totalUnhuScore / $unhuCount, 1) : null;

            // =========================================================================
            // OUTSTANDING ACHIEVEMENTS PARSER (From nested JSON block)
            // =========================================================================
            $achievements = $report->unhu_competencies['outstanding_achievements'] ?? [];
            if (is_string($achievements)) {
                $achievements = array_filter(explode("\n", $achievements));
            }

            // Inline Base64 Logo Processing to bypass SSL/Fetch bounds in DomPDF
            $logoBase64 = '';
            if ($schoolId && file_exists(public_path($schoolId.'_logo.png'))) {
                $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($schoolId.'_logo.png')));
            } elseif (file_exists(public_path('images/school_logo.png'))) {
                $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/school_logo.png')));
            }

            // Inline Base64 Photo Processing (Supports PHP 8.4 GD with PNG/JPG fallbacks)
            $photoBase64 = '';
            if ($student) {
                $photoPath = student_photo_src($student);
                if (file_exists($photoPath)) {
                    $photoBase64 = 'data:image/'.pathinfo($photoPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($photoPath));
                }
            }

            // Inline Base64 QR Code compile logic (uses flat JPEGs to bypass missing PHP GD libraries)
            $qrCodeBase64 = '';
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data='.urlencode(route('report.verify', ['hash' => $report->integrity_hash]));
            try {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $qrRawData = @file_get_contents($qrUrl, false, $context);
                if ($qrRawData) {
                    $qrCodeBase64 = 'data:image/jpeg;base64,'.base64_encode($qrRawData);
                } else {
                    $qrCodeBase64 = $qrUrl;
                }
            } catch (\Exception $e) {
                $qrCodeBase64 = $qrUrl;
            }

            $reportsCompiled[] = [
                'report' => $report,
                'student' => $student,
                'term' => $term,
                'year' => $year,
                'course' => $course,
                'level' => $level,
                'compiledSubjects' => $compiledSubjects,
                'competencies' => $competencies,
                'unhuCompiled' => $unhuCompiled,
                'overallUnhuPercentage' => $overallUnhuPercentage,
                'achievements' => $achievements,
                'classRank' => $classRank,
                'classTotal' => $classTotal,
                'streamRank' => $streamRank,
                'streamTotal' => $streamTotal,
                'logoBase64' => $logoBase64,
                'photoBase64' => $photoBase64,
                'qrCodeBase64' => $qrCodeBase64,
                'template' => $templateForCard,
            ];
        }

        if (empty($reportsCompiled)) {
            return redirect()->back()->with('error', 'No printable reports could be loaded.');
        }

        // Stream the dynamically compiled viewport
        $pdf = Pdf::loadView('modules.academics.report-card-pdf', [
            'reportsCompiled' => $reportsCompiled,
            'school' => $school,
        ])->setPaper('a4', 'portrait');

        if (count($reportsCompiled) === 1) {
            $student = $reportsCompiled[0]['student'];
            $term = $reportsCompiled[0]['term'];
            $safeAdmission = $student ? str_replace(['/', '\\'], '_', $student->admission_number) : 'Unknown_Student';

            return $pdf->stream("Report_{$safeAdmission}_{$term->name}.pdf");
        }

        return $pdf->stream('Bulk_Academic_Reports.pdf');
    }
}
