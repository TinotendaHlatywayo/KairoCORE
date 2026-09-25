<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\StudentCompetency;
use Modules\Academics\Models\Subject;
use Modules\Academics\Services\GradingScaleResolver;
use Modules\Admin\Models\SystemSetting;
use Modules\Students\Models\Enrollment;

class AcademicReportPdfController extends Controller
{
    protected array $schoolAssessmentTypes = [];
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
        $reportRecord = AcademicReport::find($ids[0] ?? null);
        $school = current_tenant() ?? ($reportRecord?->school ?? \App\Models\School::first());
        $schoolId = $school?->id ?? 1;
        if (! app()->bound('current_tenant') && $school) {
            app()->instance('current_tenant', $school);
        }

        $this->schoolAssessmentTypes = AssessmentType::where('school_id', $schoolId)->get()->keyBy('id')->all();

        $hasAnyTemplate = ReportTemplate::where('school_id', $schoolId)->exists();
        if (! $hasAnyTemplate) {
            $createUrl = route('filament.app.resources.report-templates.create');

            return response()->view('errors.missing-report-template', [
                'createUrl' => $createUrl,
            ], 422);
        }

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
        $activeOrientation = 'landscape';

        foreach ($ids as $id) {
            // Load student record even if soft-deleted; always stay inside the
            // current school so a leaked id from another tenant can never stream.
            $report = AcademicReport::with([
                'student' => fn ($q) => $q->withTrashed(),
                'term.academicYear',
                'section.course',
            ])->where('school_id', $schoolId)->find($id);

            if (! $report) {
                continue;
            }

            $student = $report->student;
            $term = $report->term;
            $year = $term?->academicYear;

            // Resolve the enrollment that backs this term BEFORE picking the
            // template: promotions/stream changes are append-only, so the old
            // row is archived and a NEW row holds the student's current
            // placement. Prefer the live (active) enrollment for the report's
            // academic year so the class, template, ranking pool and marks all
            // line up with the current placement rather than a stale
            // section_id stored on the report row.
            $enrollment = $report->resolveTermEnrollment();

            $section = $enrollment?->section ?? $report->section;
            $course = $section?->course ?? $report->section?->course;
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
                        'page_orientation' => 'landscape',
                        'line_spacing' => 1.2,
                        'table_padding' => 5,
                        'show_school_logo' => true,
                        'show_school_motto' => true,
                        'show_phone' => true,
                        'show_email' => true,
                        'show_address' => true,
                        'show_student_photo' => true,
                        'show_class_position' => true,
                        'show_stream_position' => true,
                        'ranking_scope' => 'class',
                        'show_total_marks' => true,
                        'show_class_average' => true,
                        'show_stream_average' => true,
                        'show_subject_position' => true,
                        'show_overall_subject_mark' => true,
                        'show_grade' => true,
                        'show_next_term_fees' => true,
                        'show_ubuntu_competencies' => true,
                        'show_ubuntu_percentage' => true,
                        'show_grading_keys' => true,
                        'show_qr_verification' => true,
                        'show_class_teacher_remarks' => true,
                        'show_class_teacher_signature' => true,
                        'show_principal_remarks' => true,
                        'show_headmaster_stamp' => true,
                        'show_subject_teacher_remarks' => false,
                        'ranking_scope' => 'class',
                        'page_margin_v' => 12,
                        'page_margin_h' => 15,
                        'page_border_width' => 0,
                    ],
                ]);
            }

            $activeOrientation = $templateForCard->layout_config['page_orientation'] ?? 'landscape';

            if (! $year && $enrollment) {
                $year = $enrollment->academicYear;
            }

            // Rankings / averages pool uses the year the resolved enrollment
            // actually lives in (the student's current placement when they have
            // already been promoted into a later academic year), while $year
            // stays anchored to the report's term for the printed period line.
            $scopeYear = $enrollment?->academicYear ?? $year;

            $compiledSubjects = [];
            $competencies = [];
            $assessmentTypes = collect();

$classRank = null;
            $classTotal = 0;
            $streamRank = null;
            $streamTotal = 0;
            $levelRank = null;
            $levelTotal = 0;

            if ($enrollment && $section && $course && $scopeYear) {
                $sectionEnrollmentIds = Enrollment::where('section_id', $section->id)
                    ->where('academic_year_id', $scopeYear->id)
                    ->pluck('id')
                    ->toArray();

                $streamEnrollmentIds = Enrollment::whereHas('section', fn ($q) => $q->where('course_id', $course->id))
                    ->where('academic_year_id', $scopeYear->id)
                    ->pluck('id')
                    ->toArray();

                $levelEnrollmentIds = Enrollment::whereHas('section.course', fn ($q) => $q->where('level', $level))
                    ->where('academic_year_id', $scopeYear->id)
                    ->pluck('id')
                    ->toArray();

                $allScopeEnrollmentIds = array_values(array_unique(array_merge(
                    $sectionEnrollmentIds,
                    $streamEnrollmentIds,
                    $levelEnrollmentIds
                )));

$subjects = Subject::where('school_id', $schoolId)->get();
            // The assessments displayed on the card come from the (optional)
            // template preference, always widened with the assessment types that
            // belong to this report's term (including unbound custom types). This
            // guarantees marks captured in the Marks Entry worksheet for the term
            // show up on the report card even when a template lists other types.
            $templateIncludedAssessmentIds = $templateForCard->layout_config['included_assessments'] ?? [];
            $termAssessmentIds = AssessmentType::where('school_id', $schoolId)
                ->where(fn ($q) => $q->where('term_id', $term?->id)->orWhereNull('term_id'))
                ->pluck('id')
                ->toArray();
            $includedAssessmentIds = array_values(array_unique(array_merge($templateIncludedAssessmentIds, $termAssessmentIds)));
            $assessmentTypes = AssessmentType::where('school_id', $schoolId)
                ->whereIn('id', $includedAssessmentIds)
                ->orderBy('id')
                ->get();

                $rankingScope = $templateForCard->layout_config['ranking_scope'] ?? 'class';

                $subjectRemarksRaw = (array) ($report->subject_teacher_remarks ?? []);
                $subjectRemarks = [];
                if (array_is_list($subjectRemarksRaw)) {
                    foreach ($subjectRemarksRaw as $row) {
                        if (is_array($row) && ! empty($row['subject_id'])) {
                            $subjectRemarks[$row['subject_id']] = $row['remark'] ?? null;
                        }
                    }
                } else {
                    $subjectRemarks = $subjectRemarksRaw;
                }

                $academicScoreByEnrollment = [];

                foreach ($subjects as $subject) {
                    $subjectMarks = AssessmentMark::where('subject_id', $subject->id)
                        ->whereIn('assessment_type_id', $includedAssessmentIds)
                        ->whereIn('enrollment_id', $allScopeEnrollmentIds)
                        ->get(['enrollment_id', 'assessment_type_id', 'marks_obtained']);

                    if ($subjectMarks->isEmpty()) {
                        continue;
                    }

                    // Weighted overall percentage per enrollment for this subject
                    // (Σ pct×weight / Σ weight), using only the included assessment types.
                    $pctByEnrollment = [];
                    foreach ($subjectMarks as $m) {
                        $type = $this->schoolAssessmentTypes[$m->assessment_type_id] ?? null;
                        if (! $type) {
                            continue;
                        }
                        $max = (float) $type->max_mark;
                        $weight = ($type->weight_percentage ?? 0) > 0 ? (float) $type->weight_percentage : 1;
                        $score = (float) $m->marks_obtained;
                        $pct = $max > 0 ? ($score / $max) * 100 : $score;
                        if (! isset($pctByEnrollment[$m->enrollment_id])) {
                            $pctByEnrollment[$m->enrollment_id] = [0.0, 0.0];
                        }
                        $pctByEnrollment[$m->enrollment_id][0] += $pct * $weight;
                        $pctByEnrollment[$m->enrollment_id][1] += $weight;
                    }

                    foreach ($pctByEnrollment as $eid => [$sum, $used]) {
                        if ($used > 0) {
                            $academicScoreByEnrollment[$eid][] = $sum / $used;
                        }
                    }

                    $ownPct = $pctByEnrollment[$enrollment->id] ?? null;
                    if (! $ownPct || $ownPct[1] <= 0) {
                        continue;
                    }
                    $overallWeightedMark = $ownPct[0] / $ownPct[1];

                    $classAvg = $this->scopeAverage($pctByEnrollment, $sectionEnrollmentIds);
                    $streamAvg = $this->scopeAverage($pctByEnrollment, $streamEnrollmentIds);
                    $levelAvg = $this->scopeAverage($pctByEnrollment, $levelEnrollmentIds);

                    $rankingEnrollmentIds = $sectionEnrollmentIds;
                    if ($rankingScope === 'stream' || $rankingScope === 'both') {
                        $rankingEnrollmentIds = $streamEnrollmentIds;
                    } elseif ($rankingScope === 'level') {
                        $rankingEnrollmentIds = $levelEnrollmentIds;
                    }
                    $subjectRank = $this->rankInScope($enrollment->id, $rankingEnrollmentIds, $this->pctEnrollmentMap($pctByEnrollment));

                    $assessmentMarks = [];
                    $ownMarksByType = $subjectMarks
                        ->where('enrollment_id', $enrollment->id)
                        ->pluck('marks_obtained', 'assessment_type_id');

                    foreach ($includedAssessmentIds as $assId) {
                        $scoreVal = $ownMarksByType[$assId] ?? null;
                        $type = $this->schoolAssessmentTypes[$assId] ?? null;
                        if ($scoreVal !== null && $type) {
                            $assessmentMarks[$assId] = round((float) $scoreVal) . '/'.(int) round((float) $type->max_mark);
                        } else {
                            $assessmentMarks[$assId] = '-';
                        }
                    }

                    $gradeLetter = '-';
                    if (! is_null($overallWeightedMark)) {
                        $gradeLetter = GradingScaleResolver::rating($overallWeightedMark, (int) $schoolId)['symbol'] ?? '-';
                    }

                    $compiledSubjects[] = [
                        'subject_code' => $subject->code,
                        'subject_name' => $subject->name,
                        'assessment_marks' => $assessmentMarks,
                        'final_mark' => round($overallWeightedMark),
                        'grade' => $gradeLetter,
                        'class_avg' => $classAvg,
                        'stream_avg' => $streamAvg,
                        'level_avg' => $levelAvg,
                        'subject_rank' => $subjectRank,
                        'subject_remark' => $subjectRemarks[$subject->id] ?? null,
                        'initials' => 'TR',
                    ];
                }

                // Overall class / stream / level standing derived from real weighted marks.
                $academicScores = $this->academicScoreMap($academicScoreByEnrollment);
                $classRank = $this->rankInScope($enrollment->id, $sectionEnrollmentIds, $academicScores);
                $streamRank = $this->rankInScope($enrollment->id, $streamEnrollmentIds, $academicScores);
                $levelRank = $this->rankInScope($enrollment->id, $levelEnrollmentIds, $academicScores);
                $classTotal = $this->scopeCount(array_keys($academicScores), $sectionEnrollmentIds);
                $streamTotal = $this->scopeCount(array_keys($academicScores), $streamEnrollmentIds);
                $levelTotal = $this->scopeCount(array_keys($academicScores), $levelEnrollmentIds);

                if ($level === 'primary' || $level === 'ecd') {
                    $competencies = StudentCompetency::where('enrollment_id', $enrollment->id)->get();
                }
            }

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

                    if (! empty($displayedTraits) && ! in_array($trait, $displayedTraits)) {
                        continue;
                    }

                    if (empty($rating) || trim($rating) === '') {
                        continue;
                    }

                    $cleanTrait = ucwords(str_replace('_', ' ', $trait));
                    $cleanRating = ucwords(str_replace('_', ' ', $rating));

                    $unhuCompiled[] = [
                        'trait' => $cleanTrait,
                        'rating' => $cleanRating,
                    ];

                    $ratingKey = strtolower(str_replace(' ', '_', $rating));
                    if (isset($unhuScoreMap[$ratingKey])) {
                        $totalUnhuScore += $unhuScoreMap[$ratingKey];
                        $unhuCount++;
                    }
                }
            }

            $overallUnhuPercentage = $unhuCount > 0 ? round($totalUnhuScore / $unhuCount, 1) : null;

            $achievements = $report->unhu_competencies['outstanding_achievements'] ?? [];
            if (is_string($achievements)) {
                $achievements = array_filter(explode("\n", $achievements));
            }

            // LOGO RESOLUTION (Design & Branding -> Logo Badge Upload / SystemSetting -> Tenant Logo)
            $logoBase64 = '';
            $brandingSetting = SystemSetting::where('school_id', $schoolId)
                ->where('group', 'branding')
                ->where('key', 'logo_path')
                ->first();

            if ($brandingSetting && !empty($brandingSetting->value)) {
                $val = json_decode($brandingSetting->value, true) ?? $brandingSetting->value;
                if (is_array($val)) {
                    $val = array_values($val)[0] ?? '';
                }
                if (!empty($val) && file_exists(public_path('storage/'.$val))) {
                    $logoBase64 = 'data:image/'.pathinfo($val, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents(public_path('storage/'.$val)));
                }
            }

            if (empty($logoBase64) && $schoolId && file_exists(public_path($schoolId.'_logo.png'))) {
                $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($schoolId.'_logo.png')));
            } elseif (empty($logoBase64) && $school && !empty($school->logo_path) && file_exists(public_path($school->logo_path))) {
                $logoBase64 = 'data:image/'.pathinfo($school->logo_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents(public_path($school->logo_path)));
            } elseif (empty($logoBase64) && file_exists(public_path('images/school_logo.png'))) {
                $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/school_logo.png')));
            }

            if (empty($logoBase64)) {
                $defaultLogo = public_path('images/id-card-default-logo.png');
                if (file_exists($defaultLogo)) {
                    $logoBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents($defaultLogo));
                }
            }

            $photoBase64 = '';
            if ($student) {
                $photoPath = student_photo_src($student);
                if (file_exists($photoPath)) {
                    $photoBase64 = 'data:image/'.pathinfo($photoPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($photoPath));
                }
            }

            $qrCodeBase64 = '';
            $verifyUrl = route('report.verify', [
                'hash' => $report->integrity_hash,
                'tenant' => $school?->subdomain,
            ]);
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data='.urlencode($verifyUrl);
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
                'section' => $section,
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
                'levelRank' => $levelRank,
                'levelTotal' => $levelTotal,
                'logoBase64' => $logoBase64,
                'photoBase64' => $photoBase64,
                'qrCodeBase64' => $qrCodeBase64,
                'template' => $templateForCard,
                'assessmentTypes' => $assessmentTypes,
            ];
        }

        if (empty($reportsCompiled)) {
            return redirect()->back()->with('error', 'No printable reports could be loaded.');
        }

        $pdf = Pdf::loadView('modules.academics.report-card-pdf', [
            'reportsCompiled' => $reportsCompiled,
            'school' => $school,
        ])->setPaper('a4', $activeOrientation);

        if (count($reportsCompiled) === 1) {
            $student = $reportsCompiled[0]['student'];
            $term = $reportsCompiled[0]['term'];
            $safeAdmission = $student ? str_replace(['/', '\\'], '_', $student->admission_number) : 'Unknown_Student';

            return $pdf->stream("Report_{$safeAdmission}_{$term->name}.pdf");
        }

        return $pdf->stream('Bulk_Academic_Reports.pdf');
    }

    /**
     * Mean weighted overall percentage for a subject across the given enrollment ids.
     */
    protected function scopeAverage(array $pctByEnrollment, array $scopeIds): ?float
    {
        $sum = 0;
        $count = 0;
        foreach ($scopeIds as $enrollmentId) {
            if (isset($pctByEnrollment[$enrollmentId]) && $pctByEnrollment[$enrollmentId][1] > 0) {
                $sum += $pctByEnrollment[$enrollmentId][0] / $pctByEnrollment[$enrollmentId][1];
                $count++;
            }
        }

        return $count > 0 ? $sum / $count : null;
    }

    /**
     * Position of $enrollmentId within $scopeIds, ranked by $scoreMap values descending.
     */
    protected function rankInScope(int $enrollmentId, array $scopeIds, array $scoreMap): ?int
    {
        $scores = [];
        foreach ($scopeIds as $scopeId) {
            if (isset($scoreMap[$scopeId])) {
                $scores[$scopeId] = $scoreMap[$scopeId];
            }
        }
        if (empty($scores) || ! isset($scores[$enrollmentId])) {
            return null;
        }
        arsort($scores);
        $position = array_search($enrollmentId, array_keys($scores), true);

        return $position === false ? null : $position + 1;
    }

    protected function pctEnrollmentMap(array $pctByEnrollment): array
    {
        $map = [];
        foreach ($pctByEnrollment as $enrollmentId => [$sum, $used]) {
            if ($used > 0) {
                $map[$enrollmentId] = round($sum / $used, 2);
            }
        }

        return $map;
    }

    protected function academicScoreMap(array $byEnrollment): array
    {
        $map = [];
        foreach ($byEnrollment as $enrollmentId => $subjectPcts) {
            if (! empty($subjectPcts)) {
                $map[$enrollmentId] = round(array_sum($subjectPcts) / count($subjectPcts), 2);
            }
        }

        return $map;
    }

    protected function scopeCount(array $scoredIds, array $scopeIds): int
    {
        return count(array_intersect($scoredIds, $scopeIds));
    }

    /**
     * Student-portal download: only the owning student may stream their own
     * report, and only once the school has marked the report as published.
     */
    public function studentDownload(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $report = AcademicReport::with(['student', 'term.academicYear', 'section.course'])->find($id);
        if (! $report) {
            abort(404);
        }

        $student = $report->student;
        $isOwner = $student && $student->user_id === $user->id;
        if (! $isOwner) {
            abort(403);
        }

        // School-gate: the report and the student account must belong to the
        // same school (tenant) as the authenticated user.
        if (! $user->school_id || (int) $report->school_id !== (int) $user->school_id) {
            abort(403);
        }

        if (! in_array($report->status, ['published', 'approved'], true)) {
            abort(403, 'This report card is not available for download yet.');
        }

        return $this->compileAndStreamReports([$id]);
    }
}
