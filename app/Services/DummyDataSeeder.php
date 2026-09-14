<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Assessment;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentMarksLedger;
use Modules\Academics\Models\AssessmentPlan;
use Modules\Academics\Models\AssessmentPlanComponent;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\StudentCompetency;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Admin\Models\Department;
use Modules\Admin\Models\SystemSetting;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationDocument;
use Modules\Attendance\Models\StaffAttendance;
use Modules\Attendance\Models\StudentAttendance;
use Modules\Clinic\Models\ClinicVisit;
use Modules\Clinic\Models\StudentMedicalRecord;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollOption;
use Modules\Communication\Models\PollVote;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Models\DigitalAssessmentQuestion;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\Finance\Models\FeeCategory;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\Supplier;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;
use Modules\Hostels\Models\HostelBuilding;
use Modules\Hostels\Models\HostelFloor;
use Modules\Hostels\Models\HostelRoom;
use Modules\Hostels\Models\HostelWing;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollRun;
use Modules\HR\Models\Payslip;
use Modules\HR\Models\PayslipItem;
use Modules\HR\Models\SalaryGrade;
use Modules\HR\Models\SalaryGradeHistory;
use Modules\Inventory\Models\AssetMaintenanceLog;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventorySupplier;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementOrderItem;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Models\ProcurementRequestItem;
use Modules\Knowledge\Models\KnowledgeAsset;
use Modules\Knowledge\Models\KnowledgeAssetCopy;
use Modules\Knowledge\Models\KnowledgeFormat;
use Modules\Library\Models\LibraryAuthor;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryBookCopy;
use Modules\Library\Models\LibraryCategory;
use Modules\Library\Models\LibraryFormat;
use Modules\Library\Models\LibraryIssue;
use Modules\Lms\Models\Homework;
use Modules\Lms\Models\HomeworkSubmission;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Models\ReportSchedule;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;
use Modules\Timetables\Services\TimetableGeneratorService;

/**
 * Seeds (and wipes) the playground demonstration dataset for a school tenant.
 *
 * This is the single implementation behind the `schoolcore:dummy` artisan
 * command, the dashboard "Seed/Wipe Demo Data" widget AND the registration
 * "Pre-load Demonstration Data" option, so the demo data produced everywhere
 * is identical.
 *
 * Comprehensive coverage: academics, grading, students & reports, admissions,
 * finance, HR, payroll, leave & staff attendance, inventory & procurement,
 * library, knowledge repository, clinic, hostels, timetables & attendance,
 * LMS homework, communication & polls, digital assessment and enterprise
 * reporting — enough interlinked data to exercise every module before real
 * data arrives.
 *
 * Every created row is recorded in a per-school "seed manifest"
 * (system_settings group=demo key=seed_manifest) so wipe() deletes EXACTLY
 * what this seeder created — never any user-entered data.
 */
class DummyDataSeeder
{
    /**
     * Tables that may hold manifest-tracked demo rows, in safe deletion
     * order (children before parents).
     */
    protected const MANIFEST_DELETE_ORDER = [
        'expenses',
        'expense_types',
        'suppliers',
        'expense_categories',
        'invoice_items',
        'invoices',
        'fee_structures',
        'fee_categories',
        'homework_submissions',
        'homeworks',
        'clinic_visits',
        'student_medical_records',
        'staff_attendances',
        'salary_grade_history',
        'leave_requests',
        'leave_types',
        'payslip_items',
        'payslips',
        'payroll_runs',
        'payroll_periods',
        'communication_poll_votes',
        'communication_poll_options',
        'communication_polls',
        'communication_announcements',
        'user_tasks',
        'procurement_request_items',
        'procurement_requests',
        'procurement_order_items',
        'procurement_orders',
        'inventory_suppliers',
        'inventory_locations',
        'asset_maintenance_logs',
        'knowledge_asset_copies',
        'knowledge_asset_author',
        'knowledge_assets',
        'knowledge_formats',
        'library_issues',
        'library_book_copies',
        'library_book_author',
        'library_authors',
        'student_attendances',
        'timetable_lessons',
        'time_slots',
        'classrooms',
        'digital_assessment_responses',
        'digital_assessment_attempts',
        'digital_assessment_questions',
        'digital_assessments',
        'question_bank',
        'generated_reports',
        'enterprise_report_schedules',
        'enterprise_report_templates',
        'hostel_allocations',
        'hostel_beds',
        'hostel_rooms',
        'hostel_wings',
        'hostel_floors',
        'hostel_buildings',
        'hostels',
        'fixed_assets',
        'inventory_items',
        'inventory_categories',
        'library_books',
        'library_formats',
        'library_categories',
        'employees',
        'salary_grades',
        'departments',
        'grading_points',
        'grading_scales',
        'assessment_types',
        'assessment_marks',
        'subject_papers',
        'course_subject',
        'assessment_marks_ledger',
        'enrollments',
        'academic_reports',
        'application_documents',
        'applications',
        'students',
        'sections',
        'subjects',
        'courses',
        'terms',
        'academic_years',
    ];

    /**
     * Manifest tables without a school_id column. Their ids are already
     * school-scoped by construction, so they are deleted by id only.
     */
    protected const MANIFEST_NO_SCHOOL_COLUMN = [
        'invoice_items',
        'procurement_request_items',
        'procurement_order_items',
        'grading_points',
        'library_book_author',
        'knowledge_asset_author',
        'digital_assessment_questions',
        'digital_assessment_responses',
    ];

    public function seed(int $schoolId, ?callable $log = null): array
    {
        // Demo seeding (students, reports, marks, payroll, invoices, timetables
        // ...) and its password hashing comfortably exceed the default 30s PHP
        // ceiling when triggered from a synchronous web/Livewire request, so
        // lift the per-request execution limit for the whole run.
        @set_time_limit(600);

        $log ??= fn () => null;

        // Demo seeding is idempotent: previously-seeded demo rows are removed
        // first (manifest-scoped), then the school is (re)populated. This keeps
        // the class sizes exact even after repeated "Seed" runs.
        $this->wipe($schoolId);

        $manifest = [];

        try {
            $this->runSeed($schoolId, $log, $manifest);
        } catch (\Throwable $e) {
            // Persist whatever was created so far so wipe() can still undo it.
            $this->saveManifest($schoolId, $manifest);
            throw $e;
        }

        $this->saveManifest($schoolId, $manifest);

        return [
            'students' => count($manifest['students'] ?? []),
            'reports' => count($manifest['academic_reports'] ?? []),
            'sections' => count($manifest['sections'] ?? []),
        ];
    }

    protected function runSeed(int $schoolId, callable $log, array &$manifest): void
    {
        $track = function (string $table, $ids) use (&$manifest): void {
            foreach ((array) ($ids instanceof Collection ? $ids->all() : $ids) as $id) {
                $manifest[$table][] = (int) $id;
            }
        };

        $created = function ($model) use (&$manifest): mixed {
            if ($model->wasRecentlyCreated) {
                $table = $model->getTable();
                $manifest[$table][] = (int) $model->id;
            }

            return $model;
        };

        $school = School::find($schoolId);
        $schoolType = strtolower((string) ($school->institution_type ?? 'secondary'));
        $isPrimary = in_array($schoolType, ['primary', 'both'], true);
        $isSecondary = in_array($schoolType, ['secondary', 'both'], true) && ! $isPrimary;

        // An acting user for "recorded_by"-style columns (admin if present).
        $actorId = optional(User::where('school_id', $schoolId)->where('requested_role', 'administrator')->orderBy('id')->first())->id
            ?? optional(User::where('school_id', $schoolId)->orderBy('id')->first())->id;

        // ════════════════════════════════════════════════════════════════
        // 1. ACADEMICS: year, three terms, courses, sections, subjects,
        //    course-subject links, papers and grading scale
        // ════════════════════════════════════════════════════════════════
        $year = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (! $year) {
            $year = $created(AcademicYear::create([
                'school_id' => $schoolId,
                'name' => now()->format('Y'),
                'is_active' => true,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ]));
        }

        $termDates = [
            ['Term 1', now()->startOfYear()->addDays(4), now()->startOfYear()->addMonths(3)->subDays(4)],
            ['Term 2', now()->startOfYear()->addMonths(4)->addDays(8), now()->startOfYear()->addMonths(7)],
            ['Term 3', now()->startOfYear()->addMonths(8)->addDays(6), now()->startOfYear()->addMonths(11)->addDays(2)],
        ];
        $termIds = [];
        foreach ($termDates as [$termName, $start, $end]) {
            $term = Term::where('school_id', $schoolId)->where('academic_year_id', $year->id)->where('name', $termName)->first();
            if (! $term) {
                $term = $created(Term::create([
                    'school_id' => $schoolId,
                    'name' => $termName,
                    'academic_year_id' => $year->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ]));
            }
            $termIds[$termName] = $term->id;
        }
        $term = Term::where('school_id', $schoolId)->where('academic_year_id', $year->id)->orderBy('id')->first();

        $courseSpecs = [];
        if ($isPrimary) {
            foreach (['ECD A', 'ECD B'] as $i => $n) {
                $courseSpecs[] = [$n, 'ecda', 'PR-ECD-'.chr(65 + $i), 'ecd'];
            }
            for ($g = 1; $g <= 7; $g++) {
                $courseSpecs[] = ['Grade '.$g, 'primary', 'PR-G'.$g, 'grade'];
            }
        }
        if ($isSecondary) {
            for ($f = 1; $f <= 4; $f++) {
                $courseSpecs[] = ['Form '.$f, 'secondary', 'SEC-F'.$f, 'form_1_4'];
            }
            foreach ([5, 6] as $f) {
                $courseSpecs[] = ['Form '.$f.' Sciences', 'secondary', 'SEC-F'.$f.'-SCI', 'form_5_6'];
                $courseSpecs[] = ['Form '.$f.' Commercials', 'secondary', 'SEC-F'.$f.'-COM', 'form_5_6'];
                $courseSpecs[] = ['Form '.$f.' Arts', 'secondary', 'SEC-F'.$f.'-ART', 'form_5_6'];
            }
        }

        $courseObjects = [];
        foreach ($courseSpecs as [$name, $level, $code, $scope]) {
            $course = Course::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['level' => $level, 'code' => $code]
            );
            $courseObjects[$name] = $course;
            $courseIdsByScope[$scope][] = $course->id;
            $created($course);
        }

        // Streams: two sections per level, three for the Form 5/6 subject areas.
        foreach ($courseObjects as $course) {
            $names = preg_match('/Form [56] /', (string) $course->name) ? ['A'] : ['A', 'B'];
            foreach ($names as $stream) {
                $section = Section::firstOrCreate(
                    ['school_id' => $schoolId, 'course_id' => $course->id, 'name' => $stream],
                    ['capacity' => 40]
                );
                $created($section);
            }
        }

        $sections = Section::where('school_id', $schoolId)->with('course')->get();

        $primarySubjects = [
            ['Mathematics', 'PR-MATH', 'theory'],
            ['English Language', 'PR-ENG', 'theory'],
            ['Shona Language', 'PR-SHO', 'theory'],
            ['Science & Technology', 'PR-SCI', 'practical'],
            ['Social Studies', 'PR-SOC', 'theory'],
            ['Physical Education', 'PR-PE', 'practical'],
        ];
        $secondarySubjects = [
            ['MATHEMATICS', 'SEC-MATH', 'theory'],
            ['ENGLISH LANGUAGE', 'SEC-ENG', 'theory'],
            ['SHONA LANGUAGE', 'SEC-SHO', 'theory'],
            ['COMBINED SCIENCE', 'SEC-CSC', 'practical'],
            ['GEOGRAPHY', 'SEC-GEO', 'theory'],
            ['PHYSICS', 'SEC-PHY', 'practical'],
            ['CHEMISTRY', 'SEC-CHE', 'practical'],
            ['BIOLOGY', 'SEC-BIO', 'practical'],
            ['HISTORY', 'SEC-HIS', 'theory'],
            ['PE SPORTS AND MASS DISPLAYS', 'SEC-PE', 'practical'],
            ['BUILDING TECHNOLOGY AND DESIGN', 'SEC-BTD', 'practical'],
            ['AGRICULTURE', 'SEC-AGR', 'practical'],
            ['HERITAGE', 'SEC-HER', 'theory'],
            ['COMPUTER SCIENCE', 'SEC-CS', 'practical'],
            ['FASHION AND FABRICS', 'SEC-FAF', 'practical'],
            ['FOOD AND NUTRITION', 'SEC-FAN', 'practical'],
            ['METALWORK', 'SEC-MTL', 'practical'],
            ['WOODWORK', 'SEC-WDW', 'practical'],
        ];

        $subjectById = [];
        $subjectObjects = [];
        foreach (($isPrimary ? $primarySubjects : $secondarySubjects) as [$name, $code, $type]) {
            $subject = Subject::firstOrCreate(
                ['school_id' => $schoolId, 'code' => $code],
                ['name' => $name, 'type' => $type, 'credit_weight' => 1.00, 'is_elective' => false]
            );
            $subjectObjects[$name] = $subject;
            $subjectById[$subject->id] = $subject;
            $created($subject);
        }

        // Course ⭢ subject syllabus (course_subject pivot).
        $staffUserIds = $this->seedStaff($schoolId, $actorId, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // STAFFING MODELS (teacher assignment + timetable)
        //   Model A — a Class Teacher teaches most subjects to ONE stream.
        //   Model B — a subject specialist teaches one subject across selected
        //   classes (whole grade via section_id = null, or a single stream).
        // ════════════════════════════════════════════════════════════════
        $teacherUsers = User::where('school_id', $schoolId)->get(['id', 'name']);
        $teacherIdByName = $teacherUsers->pluck('id', 'name')->all();

        $specialistBySubject = [
            'Science & Technology' => $teacherIdByName['Simbarashe Nyamupingidza'] ?? null,
            'Physical Education' => $teacherIdByName['Fadzai Mupfumira'] ?? null,
        ];

        // Class-teacher fallback pool: teaching staff only (never students or
        // non-teaching users), with the subject specialists reserved.
        $specialistIds = array_values(array_filter([
            $specialistBySubject['Science & Technology'] ?? null,
            $specialistBySubject['Physical Education'] ?? null,
        ]));

        $classTeacherPool = collect($staffUserIds['teaching_staff'] ?? [])
            ->reject(fn ($id) => in_array((int) $id, $specialistIds, true))
            ->values()
            ->all() ?: ($staffUserIds['teaching_staff'] ?: [$actorId]);

        // Each primary stream gets its own Class Teacher, so both A and B are
        // busy in the SAME periods and every teacher has distinct initials.
        $primaryClassTeachers = [
            'ECD A A' => 'Rumbidzai Mavhunga',
            'ECD A B' => 'Mercy Moyo',
            'ECD B A' => 'Grace Chengeta',
            'ECD B B' => 'Elliot Nyandoro',
            'Grade 1 A' => 'Chipo Mandizvidza',
            'Grade 1 B' => 'Maita Mutasa',
            'Grade 2 A' => 'Rudo Chikomba',
            'Grade 2 B' => 'Ruvimbo Mutsonziwa',
            'Grade 3 A' => 'Mufudzi Zhakata',
            'Grade 3 B' => 'Panashe Mudimba',
            'Grade 4 A' => 'Tafadzwa Mashava',
            'Grade 4 B' => 'Tinaye Gumbo',
            'Grade 5 A' => 'Vongai Ndlovu',
            'Grade 5 B' => 'Tatenda Sithole',
            'Grade 6 A' => 'Anesu Machiridza',
            'Grade 6 B' => 'Mufaro Mutasa',
            'Grade 7 A' => 'Tinotenda Hlatywayo',
            'Grade 7 B' => 'Atipa Mpofu',
        ];

        $classTeacherPool = $teacherUsers
            ->reject(fn ($u) => in_array($u->name, ['Simbarashe Nyamupingidza', 'Fadzai Mupfumira'], true))
            ->pluck('id')
            ->values()
            ->all() ?: ($staffUserIds['teaching_staff'] ?: [$actorId]);

        // Each primary stream gets a named Class Teacher (Named positions
        // OVERRIDE whatever is already set — a previous seed or a wipe that
        // missed sections must not leave stale teachers behind). Streams with
        // no named position keep any existing choice or fall back to the pool.
        foreach ($sections as $si => $section) {
            $ctName = $primaryClassTeachers[$section->course->name.' '.$section->name] ?? null;
            $ctId = $ctName !== null ? ($teacherIdByName[$ctName] ?? null) : null;

            if ($ctId === null) {
                if ($section->class_teacher_id) {
                    continue;
                }
                $ctId = $classTeacherPool[$si % max(1, count($classTeacherPool))];
            }

            $section->class_teacher_id = $ctId;
            Section::withoutGlobalScopes()
                ->whereKey($section->id)
                ->update(['class_teacher_id' => $ctId]);
        }

        // Create (or reattach) one course_subject row per scope. Re-seeding after
        // a wipe finds the rows again so lesson placement stays idempotent.
        $upsertCourseSubject = function (int $schoolId, Course $course, ?Subject $subject, int $teacherId, int $periodsPerWeek, ?int $sectionId = null, string $role = 'main') use (&$manifest): void {
            if (! $subject) {
                return;
            }

            $existing = DB::table('course_subject')
                ->where('school_id', $schoolId)
                ->where('course_id', $course->id)
                ->where('subject_id', $subject->id)
                ->where('section_id', $sectionId)
                ->first();

            if ($existing) {
                $manifest['course_subject'][] = (int) $existing->id;

                return;
            }

            $pivotId = DB::table('course_subject')->insertGetId([
                'school_id' => $schoolId,
                'course_id' => $course->id,
                'subject_id' => $subject->id,
                'section_id' => $sectionId,
                'teacher_id' => $teacherId,
                'role' => $role,
                'periods_per_week' => $periodsPerWeek,
                'room_preference' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $manifest['course_subject'][] = (int) $pivotId;
        };

        $courseTeacherByCourse = [];

        // Rebuild the syllabus from scratch: stale course-subject rows (old
        // course-level repeats with different/wrong teachers) surviving a wipe
        // would otherwise bleed extra requirements into auto-placement.
        DB::table('course_subject')->where('school_id', $schoolId)->delete();

        if ($isPrimary) {
            // ════════════════════════════════════════════════════════════════
            // MODEL A + MODEL B (primary) — two assignment styles coexist:
            //   1. Every stream has its own Class Teacher who teaches 5 subjects
            //      (25 periods) to THAT ONE stream → the whole class is being
            //      taught while A and B are both busy in the same slots.
            //   2. Subject specialists cover the 6th subject (5 periods):
            //      - Simbarashe Nyamupingidza → Science & Technology for the
            //        WHOLE of Grade 4 and Grade 5 (course-level row).
            //      - Fadzai Mupfumira → Physical Education for Grade 3 A only.
            // ════════════════════════════════════════════════════════════════
            $scienceSpecialistId = $specialistBySubject['Science & Technology'] ?? null;
            $peSpecialistId = $specialistBySubject['Physical Education'] ?? null;

            if ($scienceSpecialistId) {
                $science = $subjectObjects['Science & Technology'] ?? null;
                foreach ($courseObjects as $courseName => $course) {
                    if (! in_array($courseName, ['Grade 4', 'Grade 5'], true)) {
                        continue;
                    }
                    $upsertCourseSubject($schoolId, $course, $science, $scienceSpecialistId, 5, null, 'specialist');
                }
            }

            if ($peSpecialistId) {
                foreach ($sections as $section) {
                    if (trim($section->course->name.' '.$section->name) !== 'Grade 3 A') {
                        continue;
                    }
                    $upsertCourseSubject($schoolId, $section->course, $subjectObjects['Physical Education'] ?? null, $peSpecialistId, 5, $section->id, 'specialist');
                }
            }

            $primaryCore = ['Mathematics', 'English Language', 'Shona Language', 'Social Studies'];

            foreach ($sections as $section) {
                $course = $section->course;
                $ctId = $section->class_teacher_id;
                if (! $ctId) {
                    continue;
                }
                $sectionLabel = trim($course->name.' '.$section->name);

                foreach ($primaryCore as $subjectName) {
                    $upsertCourseSubject($schoolId, $course, $subjectObjects[$subjectName] ?? null, $ctId, 5, $section->id);
                }

                $scienceCovered = $scienceSpecialistId && in_array($course->name, ['Grade 4', 'Grade 5'], true);
                $peCovered = $peSpecialistId && $sectionLabel === 'Grade 3 A';

                if (! $scienceCovered) {
                    $upsertCourseSubject($schoolId, $course, $subjectObjects['Science & Technology'] ?? null, $ctId, 5, $section->id);
                }
                if (! $peCovered) {
                    $upsertCourseSubject($schoolId, $course, $subjectObjects['Physical Education'] ?? null, $ctId, 5, $section->id);
                }

                Course::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->whereKey($course->id)
                    ->update(['teacher_id' => $ctId]);
            }
        } else {
            $artsSubjects = ['ENGLISH LANGUAGE', 'MATHEMATICS', 'SHONA LANGUAGE', 'HISTORY', 'HERITAGE', 'PE SPORTS AND MASS DISPLAYS'];
            $commercialsSubjects = ['MATHEMATICS', 'ENGLISH LANGUAGE', 'GEOGRAPHY', 'HISTORY', 'HERITAGE', 'PE SPORTS AND MASS DISPLAYS'];
            $sciencesSubjects = ['MATHEMATICS', 'PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'COMBINED SCIENCE', 'ENGLISH LANGUAGE'];
            $subjectKeyFor = function (Course $course) use ($artsSubjects, $commercialsSubjects, $sciencesSubjects, $secondarySubjects, $primarySubjects): array {
                $upper = strtoupper((string) $course->name);
                if (str_contains($upper, 'SCIENTIFIC') || str_contains($upper, 'SCI') || str_contains($upper, ' SCI ')) {
                    return $sciencesSubjects;
                }
                if (str_contains($upper, 'COMMERCIAL') || str_contains($upper, 'COM')) {
                    return $commercialsSubjects;
                }
                if (str_contains($upper, ' ARTS') || str_contains($upper, 'ART')) {
                    return $artsSubjects;
                }
                if (str_contains($upper, 'FORM')) {
                    return $secondarySubjects;
                }

                return $primarySubjects;
            };

            $courseTeacherIdx = 0;
            foreach ($courseObjects as $course) {
                if (preg_match('/Form [56] /', (string) $course->name)) {
                    // Form 5/6: keep the existing course-level subject sets
                    // (Sciences / Commercials / Arts) assigned to the whole
                    // course — every stream of that subject area shares them.
                    $subjectNames = array_map(fn ($s) => is_array($s) ? $s[0] : $s, $subjectKeyFor($course));
                    foreach ($subjectNames as $si => $subjectName) {
                        $subject = $subjectObjects[$subjectName] ?? null;
                        if (! $subject) {
                            continue;
                        }
                        $teacherId = $staffUserIds['teaching_staff'][$courseTeacherIdx % max(1, count($staffUserIds['teaching_staff']))] ?? $actorId;
                        $upsertCourseSubject($schoolId, $course, $subject, $teacherId, $si === 0 ? 6 : 4);
                    }
                    $courseTeacherByCourse[$course->id] = $staffUserIds['teaching_staff'][$courseTeacherIdx % max(1, count($staffUserIds['teaching_staff']))] ?? $actorId;
                    $courseTeacherIdx++;
                } else {
                    // Form 1-4: streams take the SAME core subjects but DIFFER
                    // in their practical/option subjects. Stream A leans
                    // sciences (Physics/Chemistry/Biology/Computer Science);
                    // Stream B leans humanities & vocational (Geography, History,
                    // Building Tech, Fashion & Fabrics). All streams study the
                    // same core count, but the practical set differs per stream.
                    $courseSections = $sections->where('course_id', $course->id);

                    foreach ($courseSections as $section) {
                        $isB = strtoupper((string) $section->name) === 'B';
                        $subjectSet = $this->streamSubjectSetFor($course, $isB, $subjectObjects);

                        foreach ($subjectSet as $si => $subject) {
                            $teacherId = $staffUserIds['teaching_staff'][$courseTeacherIdx % max(1, count($staffUserIds['teaching_staff']))] ?? $actorId;
                            $upsertCourseSubject($schoolId, $course, $subject, $teacherId, $si === 0 ? 6 : 4, $section->id);
                        }
                    }

                    $teacherId = $staffUserIds['teaching_staff'][$courseTeacherIdx % max(1, count($staffUserIds['teaching_staff']))] ?? $actorId;
                    $courseTeacherByCourse[$course->id] = $teacherId;
                    $courseTeacherIdx++;

                    Course::withoutGlobalScopes()
                        ->where('school_id', $schoolId)
                        ->whereKey($course->id)
                        ->update(['teacher_id' => $teacherId]);
                }

                // Subject papers for the practical science subjects (secondary).
                if ($isSecondary) {
                    foreach (['COMBINED SCIENCE', 'PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'AGRICULTURE', 'COMPUTER SCIENCE', 'BUILDING TECHNOLOGY AND DESIGN', 'FASHION AND FABRICS', 'FOOD AND NUTRITION', 'PE SPORTS AND MASS DISPLAYS'] as $paperSubject) {
                        $subj = $subjectObjects[$paperSubject] ?? null;
                        if (! $subj) {
                            continue;
                        }
                        foreach (['Paper 1', 'Paper 2'] as $paperName) {
                            $exists = DB::table('subject_papers')
                                ->where('school_id', $schoolId)
                                ->where('subject_id', $subj->id)
                                ->where('name', $paperName)
                                ->exists();
                            if (! $exists) {
                                $paperId = DB::table('subject_papers')->insertGetId([
                                    'school_id' => $schoolId,
                                    'subject_id' => $subj->id,
                                    'name' => $paperName,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $manifest['subject_papers'][] = (int) $paperId;
                            }
                        }
                    }
                }
            }
        }

        // Form teachers on every Course (clears the "forms have no teachers"
        // readiness warning) + a Class Teacher on every stream section.
        foreach ($courseTeacherByCourse as $courseId => $teacherId) {
            Course::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereKey($courseId)
                ->update(['teacher_id' => $teacherId]);
        }

        $classTeacherPool = $staffUserIds['teaching_staff'] ?: [$actorId];
        foreach (Section::where('school_id', $schoolId)->get() as $si => $section) {
            if (! $section->class_teacher_id) {
                Section::withoutGlobalScopes()
                    ->whereKey($section->id)
                    ->update(['class_teacher_id' => $classTeacherPool[$si % max(1, count($classTeacherPool))]]);
            }
        }

        $scale = GradingScale::where('school_id', $schoolId)->first();
        if (! $scale) {
            $scale = $created(GradingScale::create(['school_id' => $schoolId, 'name' => 'Standard O-Level Scale']));
            foreach ([
                ['A', 80, 100, 'Excellent'],
                ['B', 70, 79, 'Very Good'],
                ['C', 60, 69, 'Good'],
                ['D', 50, 59, 'Satisfactory'],
                ['E', 40, 49, 'Pass'],
                ['U', 0, 39, 'Ungraded'],
            ] as [$symbol, $min, $max, $remark]) {
                $created(GradingPoint::create([
                    'grading_scale_id' => $scale->id,
                    'symbol' => $symbol,
                    'min_score' => $min,
                    'max_score' => $max,
                    'remark' => $remark,
                ]));
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 2. ADMISSIONS: applications received for the upcoming intake
        // ════════════════════════════════════════════════════════════════
        $this->seedApplications($schoolId, $courseObjects, $track, $created, $manifest);

        // ════════════════════════════════════════════════════════════════
        // 3. STUDENTS + ENROLLMENTS + ASSESSMENTS + MARKS + REPORTS
        // ════════════════════════════════════════════════════════════════
        $studentOffset = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->count();

        $firstNamesMale = ['Tatenda', 'Tinashe', 'Tariro', 'Tanaka', 'Kudakwashe', 'Farai', 'Simbarashe', 'Rufaro', 'Munashe', 'Tendai'];
        $firstNamesFemale = ['Ruvimbo', 'Chipo', 'Nyasha', 'Rudo', 'Tsitsi', 'Fadzai', 'Sekai', 'Nokutenda', 'Tadiwanashe', 'Rutendo'];
        $surnames = ['Moyo', 'Sibanda', 'Ndlovu', 'Dube', 'Mutasa', 'Gumbo', 'Zhou', 'Shumba', 'Mpofu', 'Maphosa'];
        $houses = ['Nyanga', 'Chiadzwa', 'Chimanimani', 'Vumba'];
        $suburbs = ['Borrowdale', 'Avondale', 'Mount Pleasant', 'Chisipite', 'Glen Lorne', 'Highlands', 'Borrowdale Brooke', 'Carrickley', 'Mandara', 'Greendale'];
        $streets = ['Links Lane', 'Borrowdale Road', "St Anne's Road", 'Enterprise Road', 'Avondale Drive', 'Mount Pleasant Heights', 'Chisipite Drive', 'Lytton Road', 'Greendale Road', 'Borrowdale Close'];
        $bloodGroups = ['O+', 'O-', 'A+', 'B+', 'AB+'];
        $medicalNotes = ['None', 'Mild pollen allergy', 'Requires asthma inhaler near sports field', 'None', 'None'];

        $studentCount = $studentOffset;
        $reportCount = 0;

        foreach ($sections as $section) {
            $course = $section->course;
            if (! $course) {
                continue;
            }

            $log("Seeding 5 students into class: {$course->name} {$section->name}...");

            $courseSubjects = $this->syllabusSubjectsFor($course, $primarySubjects, $secondarySubjects, $subjectObjects);
            $firstSubject = $courseSubjects[0] ?? $subjectObjects[array_key_first($subjectObjects)];
            if (! $firstSubject) {
                continue;
            }

            $plan = AssessmentPlan::firstOrCreate([
                'school_id' => $schoolId,
                'term_id' => $term->id,
                'course_id' => $course->id,
                'subject_id' => $firstSubject->id,
            ], [
                'created_by_id' => $actorId ?? 1,
            ]);
            $created($plan);

            $compHomework = AssessmentPlanComponent::firstOrCreate([
                'assessment_plan_id' => $plan->id,
                'name' => 'Homework',
            ], [
                'weight_percentage' => 30.00,
                'evaluation_rule' => 'average',
            ]);

            $compExam = AssessmentPlanComponent::firstOrCreate([
                'assessment_plan_id' => $plan->id,
                'name' => 'Final Exam',
            ], [
                'weight_percentage' => 70.00,
                'evaluation_rule' => 'highest',
            ]);

            $assessmentHomework = Assessment::firstOrCreate([
                'school_id' => $schoolId,
                'assessment_plan_component_id' => $compHomework->id,
                'section_id' => $section->id,
                'name' => 'Progress Quiz',
            ], [
                'assessment_date' => now()->subDays(10),
                'max_mark' => 50.00,
                'included_in_report' => true,
                'status' => 'locked',
                'created_by_id' => $actorId ?? 1,
            ]);

            $assessmentExam = Assessment::firstOrCreate([
                'school_id' => $schoolId,
                'assessment_plan_component_id' => $compExam->id,
                'section_id' => $section->id,
                'name' => 'Main Examination',
            ], [
                'assessment_date' => now()->subDays(2),
                'max_mark' => 100.00,
                'included_in_report' => true,
                'status' => 'locked',
                'created_by_id' => $actorId ?? 1,
            ]);

            $age = match (true) {
                str_contains(strtolower((string) $course->name), 'ecd') => 5,
                preg_match('/Grade\s*(\d)/i', (string) $course->name, $m) => 5 + intval($m[1]),
                preg_match('/Form\s*([1-4])/i', (string) $course->name, $m) => 12 + intval($m[1]),
                preg_match('/Form [56]/i', (string) $course->name) => 17,
                default => 10,
            };

            for ($k = 0; $k < 5; $k++) {
                $studentCount++;
                $gender = $k % 2 === 0 ? 'female' : 'male';
                $firstName = $gender === 'female'
                    ? $firstNamesFemale[rand(0, 9)]
                    : $firstNamesMale[rand(0, 9)];
                $surname = $surnames[rand(0, 9)];

                $stuIdNumber = 'TEST-STU-'.$schoolId.'-'.str_pad((string) $studentCount, 5, '0', STR_PAD_LEFT);

                $student = Student::create([
                    'school_id' => $schoolId,
                    'student_id_number' => $stuIdNumber,
                    'admission_number' => 'TEST-ADM-'.$schoolId.'-'.str_pad((string) $studentCount, 4, '0', STR_PAD_LEFT),
                    'first_name' => $firstName,
                    'last_name' => $surname,
                    'gender' => $gender,
                    'date_of_birth' => now()->subYears($age)->subDays(rand(1, 280)),
                    'admission_date' => $year->start_date ?? now()->startOfYear(),
                    'national_id' => '65 '.str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT).' '.($gender === 'female' ? 'F' : 'M').' '.str_pad((string) rand(1, 99), 2, '0', STR_PAD_LEFT),
                    'physical_address' => rand(1, 180).' '.$streets[rand(0, 9)].', '.$suburbs[rand(0, 9)].', Harare',
                    'phone' => '+263 77 '.rand(100000, 999999),
                    'status' => 'active',
                    'card_expiry_date' => now()->addMonths(rand(6, 18))->toDateString(),
                    'card_status' => 'active',
                    'photo_approved_at' => now()->subMonths(rand(1, 8)),
                    'photo_approved_by' => $actorId ?? 1,
                    'boarding_status' => $k % 3 === 0 ? 'boarder' : 'day_scholar',
                    'house' => $houses[rand(0, 3)],
                    'blood_group' => $bloodGroups[rand(0, 4)],
                    'medical_notes' => $medicalNotes[rand(0, 4)],
                    'emergency_contact_name' => 'Mr. '.$surname.' Senior',
                    'emergency_contact_phone' => '+263 77 '.rand(100000, 999999),
                ]);
                $track('students', [$student->id]);

                $enrollment = Enrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                ]);
                $track('enrollments', [$enrollment->id]);

                AssessmentMarksLedger::create([
                    'school_id' => $schoolId,
                    'enrollment_id' => $enrollment->id,
                    'assessment_id' => $assessmentHomework->id,
                    'marks_obtained' => rand(30, 50),
                    'status' => 'present',
                ]);
                AssessmentMarksLedger::create([
                    'school_id' => $schoolId,
                    'enrollment_id' => $enrollment->id,
                    'assessment_id' => $assessmentExam->id,
                    'marks_obtained' => rand(55, 98),
                    'status' => 'present',
                ]);

                foreach ($termIds as $reportTermId) {
                    $unhuRatings = [
                        'respect' => rand(0, 1) ? 'excellent' : 'very_good',
                        'honesty' => rand(0, 1) ? 'very_good' : 'satisfactory',
                        'responsibility' => rand(0, 1) ? 'excellent' : 'very_good',
                        'discipline' => rand(0, 1) ? 'very_good' : 'excellent',
                        'teamwork' => rand(0, 1) ? 'satisfactory' : 'very_good',
                    ];
                    $outstandingAchievements = array_values(array_filter([
                        rand(0, 1) ? 'Winner of 800m at the inter-house sports gala' : null,
                        rand(0, 1) ? 'Best behaved student of the term' : null,
                        rand(0, 1) ? 'Outstanding participation in the school choir' : null,
                    ]));

                    $report = AcademicReport::create([
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                        'section_id' => $section->id,
                        'term_id' => $reportTermId,
                        'unhu_competencies' => array_merge($unhuRatings, [
                            'outstanding_achievements' => $outstandingAchievements,
                        ]),
                        'overall_score' => rand(55, 92) / 10,
                        'status' => 'approved',
                        'teacher_comment' => 'A focused and highly diligent student who shows steady, remarkable progress.',
                        'headmaster_comment' => 'Impressive score performance this term. Maintain the clean and excellent focus.',
                        'integrity_hash' => hash_hmac('sha256', $stuIdNumber.'-'.$reportTermId, config('app.key')),
                    ]);
                    $track('academic_reports', [$report->id]);
                    $reportCount++;
                }

                if (in_array($level, ['primary', 'ecd'], true)) {
                    $competencyAreas = [
                        'Art' => [8, 10],
                        'Music' => [7, 10],
                        'Physical Education' => [8, 10],
                        'Gardening' => [6, 9],
                    ];
                    foreach ($competencyAreas as $skillArea => [$min, $max]) {
                        StudentCompetency::create([
                            'school_id' => $schoolId,
                            'enrollment_id' => $enrollment->id,
                            'skill_area' => $skillArea,
                            'score' => rand($min, $max),
                            'remark' => 'Satisfactory',
                        ]);
                    }
                }
            }
        }

        // Publish the demo students' reports so the student portal immediately
        // has published report cards to display under the school's subdomain.
        $demoStudentIds = Student::where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->pluck('id');
        AcademicReport::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $demoStudentIds)
            ->where('status', 'approved')
            ->update(['status' => 'published']);

        $studentIdsList = $manifest['students'] ?? [];

        // Demo login accounts: one per seeded student so the student portal
        // (results, continuous assessment, digital assessments, gamification)
        // is immediately demonstrable under the school's subdomain.
        $this->ensureDemoStudentAccounts($schoolId);

        // ════════════════════════════════════════════════════════════════
        // 4. FINANCE: fee structures, invoices & items, expenses, suppliers
        // ════════════════════════════════════════════════════════════════
        $this->seedFinance($schoolId, $year, $term, $studentIdsList, $actorId, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 5. HR & PAYROLL: leave, staff attendance, payroll run & payslips
        // ════════════════════════════════════════════════════════════════
        $this->seedLeaveAndPayroll($schoolId, $actorId, $staffUserIds, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 6. INVENTORY & PROCUREMENT, fixed assets
        // ════════════════════════════════════════════════════════════════
        $this->seedInventoryAndProcurement($schoolId, $actorId, $staffUserIds, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 7. LIBRARY + KNOWLEDGE REPOSITORY
        // ════════════════════════════════════════════════════════════════
        $this->seedLibraryAndKnowledge($schoolId, $actorId, $studentIdsList, $staffUserIds, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 8. CLINIC: medical records + visits
        // ════════════════════════════════════════════════════════════════
        $this->seedClinic($schoolId, $studentIdsList, $bloodGroups, $actorId, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 9. HOSTELS: boys + girls houses, floors, wings, rooms, allocations
        // ════════════════════════════════════════════════════════════════
        $this->seedHostels($schoolId, $year, $studentIdsList, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 10. TIMETABLE + STUDENT ATTENDANCE
        // ════════════════════════════════════════════════════════════════
        $this->seedTimetableAndAttendance($schoolId, $year, $term, $sections, $studentIdsList, $staffUserIds, $actorId, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 11. LMS HOMEWORK + SUBMISSIONS
        // ════════════════════════════════════════════════════════════════
        $this->seedHomework($schoolId, $sections, $studentIdsList, $track, $created);

        // ════════════════════════════════════════════════════════════════
        // 12. COMMUNICATION: announcements, tasks, polls
        // ════════════════════════════════════════════════════════════════
        $this->seedCommunication($schoolId, $actorId, $staffUserIds, $track);

        // ════════════════════════════════════════════════════════════════
        // 13. DIGITAL ASSESSMENT (LMS)
        // ════════════════════════════════════════════════════════════════
        $this->seedDigitalAssessment($schoolId, $year, $term, $sections, $primarySubjects, $secondarySubjects, $subjectObjects, $actorId, $track);

        // ════════════════════════════════════════════════════════════════
        // 13b. TRADITIONAL SUBJECT MARKS (4 subjects per course)
        // ════════════════════════════════════════════════════════════════
        $this->seedSubjectMarks($schoolId, $term, $actorId, $track);

        // ════════════════════════════════════════════════════════════════
        // 14. ENTERPRISE REPORTING: templates, compiled reports, schedules
        // ════════════════════════════════════════════════════════════════
        $this->seedEnterpriseReports($schoolId, $term, $actorId, $track);
    }

    /**
     * Returns the Subject set for a single Form 1-4 stream section. Every
     * stream shares the same CORE subjects, but differs in practical/option
     * subjects so learners in different streams study a different practical
     * set while still carrying the same core load.
     */
    protected function streamSubjectSetFor(Course $course, bool $isB, array $subjectObjects): array
    {
        $core = ['MATHEMATICS', 'ENGLISH LANGUAGE', 'SHONA LANGUAGE', 'COMBINED SCIENCE', 'HERITAGE', 'PE SPORTS AND MASS DISPLAYS'];

        $practicals = $isB
            ? ['GEOGRAPHY', 'HISTORY', 'BUILDING TECHNOLOGY AND DESIGN', 'FASHION AND FABRICS']
            : ['PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'COMPUTER SCIENCE'];

        $names = array_merge($core, $practicals);

        $out = [];
        foreach ($names as $name) {
            if (isset($subjectObjects[$name])) {
                $out[] = $subjectObjects[$name];
            }
        }

        return $out;
    }

    protected function syllabusSubjectsFor(Course $course, array $primarySubjects, array $secondarySubjects, array $subjectObjects): array
    {
        $upper = strtoupper((string) $course->name);
        if (str_contains($upper, 'SCI') && ! str_contains($upper, 'FORM') && $course->level !== 'primary') {
            // handled via written mapping below
        }
        if (str_contains($upper, 'COMMERCIAL') || str_contains($upper, 'COM')) {
            $names = ['MATHEMATICS', 'ENGLISH LANGUAGE', 'GEOGRAPHY', 'HISTORY', 'HERITAGE', 'PE SPORTS AND MASS DISPLAYS'];
        } elseif (str_contains($upper, 'ART')) {
            $names = ['ENGLISH LANGUAGE', 'MATHEMATICS', 'SHONA LANGUAGE', 'HISTORY', 'HERITAGE', 'PE SPORTS AND MASS DISPLAYS'];
        } elseif (str_contains($upper, 'SCI')) {
            $names = ['MATHEMATICS', 'PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'COMBINED SCIENCE', 'ENGLISH LANGUAGE'];
        } elseif (str_contains($upper, 'FORM')) {
            $names = array_column($secondarySubjects, 0);
        } else {
            $names = array_column($primarySubjects, 0);
        }

        $out = [];
        foreach ($names as $name) {
            if (isset($subjectObjects[$name])) {
                $out[] = $subjectObjects[$name];
            }
        }

        return $out;
    }

    // ────────────────────────────────────────────────────────────────────
    // Humans
    // ────────────────────────────────────────────────────────────────────

    protected function seedStaff(int $schoolId, ?int $actorId, callable $track, callable $created): array
    {
        // Departments (Admin module).
        foreach ([
            ['Academic', 'ACD', 'academic'],
            ['Administration', 'ADM', 'administrative'],
            ['Finance', 'FIN', 'administrative'],
            ['Health & Wellness', 'HLT', 'support'],
            ['ICT & Digital Learning', 'ICT', 'support'],
            ['Estates, Transport & Security', 'EST', 'support'],
            ['Library & Resource Centre', 'LIB', 'support'],
        ] as [$name, $code, $type]) {
            if (Department::where('school_id', $schoolId)->where('code', $code)->exists()) {
                continue;
            }
            $dept = Department::create([
                'school_id' => $schoolId,
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'status' => 'active',
            ]);
            $track('departments', [$dept->id]);
        }

        $gradeDefs = [
            ['D1 — Senior Management', 2400, 300, 180, 120],
            ['T1 — Senior Teacher', 1500, 160, 100, 60],
            ['T2 — Teacher', 1100, 120, 80, 40],
            ['S1 — Support Staff', 650, 60, 40, 0],
        ];
        $gradeIds = [];
        foreach ($gradeDefs as [$name, $base, $housing, $transport, $duty]) {
            $grade = SalaryGrade::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                [
                    'base_salary' => $base,
                    'hourly_rate' => round($base / 160, 2),
                    'housing_allowance' => $housing,
                    'transport_allowance' => $transport,
                    'duty_allowance' => $duty,
                    'overtime_eligible' => $base < 800,
                ]
            );
            $gradeIds[$name] = $grade->id;
            $created($grade);
        }

        $staffSpecs = [
            // Administrator role (5+)
            ['Grace', 'Mhaka', 'female', 'Headmistress', 'Administration', 'administrator', 'D1 — Senior Management'],
            ['Petros', 'Ngwenya', 'male', 'Deputy Headmaster', 'Academic', 'administrator', 'T1 — Senior Teacher'],
            ['Runyararo', 'Demba', 'female', 'School Registrar (Admissions)', 'Administration', 'administrator', 'S1 — Support Staff'],
            ['Munashe', 'Chayambuka', 'male', 'ICT Manager — Systems Administrator', 'ICT & Digital Learning', 'administrator', 'T1 — Senior Teacher'],
            ['Agness', 'Taruvinga', 'female', 'HR & Finance Administrator', 'Finance', 'administrator', 'S1 — Support Staff'],
            // Teaching staff (5+)
            ['Chipo', 'Mandizvidza', 'female', 'Teacher — Mathematics', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Maita', 'Mutasa', 'female', 'Teacher — English Language', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Simbarashe', 'Nyamupingidza', 'male', 'Teacher — Sciences (Physics & Chemistry)', 'Academic', 'teaching_staff', 'T1 — Senior Teacher'],
            ['Rudo', 'Chikomba', 'female', 'Teacher — Humanities (History & Heritage)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Kudakwashe', 'Zhakata', 'male', 'Teacher — Practicals (Agriculture & BTD)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Fadzai', 'Mupfumira', 'female', 'Teacher — PE, Sports & Mass Displays', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Rumbidzai', 'Mavhunga', 'female', 'Class Teacher — Infant (ECD A)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Mercy', 'Moyo', 'female', 'Class Teacher — Infant (ECD A)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Grace', 'Chengeta', 'female', 'Class Teacher — Infant (ECD B)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Elliot', 'Nyandoro', 'male', 'Class Teacher — Infant (ECD B)', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Ruvimbo', 'Mutsonziwa', 'female', 'Class Teacher — Grade 2', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Mufudzi', 'Zhakata', 'male', 'Class Teacher — Grade 3 A', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Panashe', 'Mudimba', 'male', 'Class Teacher — Grade 3 B', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Tafadzwa', 'Mashava', 'male', 'Class Teacher — Grade 4 A', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Tinaye', 'Gumbo', 'female', 'Class Teacher — Grade 4 B', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Vongai', 'Ndlovu', 'female', 'Class Teacher — Grade 5 A', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Tatenda', 'Sithole', 'male', 'Class Teacher — Grade 5 B', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Anesu', 'Machiridza', 'female', 'Class Teacher — Grade 6 A', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Mufaro', 'Mutasa', 'female', 'Class Teacher — Grade 6 B', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            ['Tinotenda', 'Hlatywayo', 'male', 'Class Teacher — Grade 7 A', 'Academic', 'teaching_staff', 'T1 — Senior Teacher'],
            ['Atipa', 'Mpofu', 'female', 'Class Teacher — Grade 7 B', 'Academic', 'teaching_staff', 'T2 — Teacher'],
            // Non-teaching staff (5+)
            ['Sharon', 'Chigumba', 'female', 'School Bursar', 'Finance', 'non_teaching_staff', 'S1 — Support Staff'],
            ['Tafadzwa', 'Moyo', 'male', 'School Nurse', 'Health & Wellness', 'non_teaching_staff', 'S1 — Support Staff'],
            ['Beauty', 'Zvoma', 'female', 'Receptionist & Admin Assistant', 'Administration', 'non_teaching_staff', 'S1 — Support Staff'],
            ['Lloyd', 'Dube', 'male', 'ICT Technician', 'ICT & Digital Learning', 'non_teaching_staff', 'S1 — Support Staff'],
            ['Tanaka', 'Mpofu', 'male', 'Driver & Groundsman', 'Estates, Transport & Security', 'non_teaching_staff', 'S1 — Support Staff'],
            ['Nyasha', 'Chirwa', 'female', 'School Librarian', 'Library & Resource Centre', 'non_teaching_staff', 'S1 — Support Staff'],
        ];

        $idsByRole = [];
        foreach ($staffSpecs as $n => [$first, $last, $gender, $designation, $department, $role, $gradeName]) {
            $demoEmail = strtolower($first.'.'.$last.'@demo.schoolcore.test');

            $existingEmp = Employee::withoutGlobalScopes()
                ->where('school_id', $schoolId)->where('email', $demoEmail)->first();
            if ($existingEmp) {
                $idsByRole[$role][] = $existingEmp->user_id;

                continue;
            }

            $employee = Employee::create([
                'school_id' => $schoolId,
                'employee_number' => 'TEST-STF-'.$schoolId.'-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                'national_id' => 'TEST-'.rand(10, 99).'-'.rand(100000, 999999).'X'.rand(10, 99),
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(28, 58))->subDays(rand(1, 300)),
                'phone_number' => '+263 71 '.rand(100000, 999999),
                'email' => $demoEmail,
                'physical_address' => rand(1, 200).' '.collect(['Samora Machel Ave', 'Josiah Tongogara St', 'Robert Mugabe Rd'])->random().', Harare',
                'emergency_contact_name' => 'Relative of '.$first,
                'emergency_contact_phone' => '+263 78 '.rand(100000, 999999),
                'department' => $department,
                'designation' => $designation,
                'role' => $role,
                'employment_type' => 'Permanent',
                'date_joined' => now()->subYears(rand(1, 10))->subMonths(rand(0, 11)),
                'current_grade_id' => $gradeIds[$gradeName],
            ]);
            $track('employees', [$employee->id]);
            $idsByRole[$role][] = $employee->user_id; // observer gives us the linked user
        }

        return [
            'teaching_staff' => array_values(array_filter($idsByRole['teaching_staff'] ?? [])),
            'non_teaching_staff' => array_values(array_filter($idsByRole['non_teaching_staff'] ?? [])),
            'administrator' => array_values(array_filter($idsByRole['administrator'] ?? [])),
        ];
    }

    protected function seedApplications(int $schoolId, array $courseObjects, callable $track, callable $created, array &$manifest): void
    {
        $appSeq = Application::withoutGlobalScopes()->where('school_id', $schoolId)->where('application_number', 'LIKE', 'TEST-APP-%')->count();
        $parentsFemale = ['Sekai', 'Nomsa', 'Chiedza', 'Vongai'];
        $parentsMale = ['Charles', 'Tonderai', 'Blessing', 'Webster'];
        $courses = array_values($courseObjects);

        $statuses = ['enrolled', 'enrolled', 'confirmed', 'pending', 'pending'];
        foreach ($statuses as $i => $status) {
            $appSeq++;
            $course = $courses[$i % count($courses)];
            $gender = $i % 2 === 0 ? 'female' : 'male';

            $app = Application::create([
                'school_id' => $schoolId,
                'application_number' => 'TEST-APP-'.$schoolId.'-'.str_pad((string) $appSeq, 4, '0', STR_PAD_LEFT),
                'first_name' => $gender === 'female' ? 'Rutendo' : 'Mufaro',
                'last_name' => $course->name === 'Form 1' ? 'Maringa' : ['Maringa', 'Zvandaka', 'Mhazha', 'Dzvairo'][$i % 4],
                'national_id' => '65 '.str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT).' '.($gender === 'female' ? 'F' : 'M').' '.str_pad((string) rand(1, 99), 2, '0', STR_PAD_LEFT),
                'email' => 'parent'.$appSeq.'@demo.schoolcore.test',
                'gender' => $gender,
                'date_of_birth' => now()->subYears($course->name === 'Form 1' ? 12 : 6)->subDays(rand(1, 200)),
                'parent_name' => ($gender === 'female' ? $parentsFemale : $parentsMale)[$i % 4].' '.rand(1000, 9999),
                'parent_email' => 'guardian'.$appSeq.'@demo.schoolcore.test',
                'parent_phone' => '+263 77 '.rand(100000, 999999),
                'parent_relationship' => 'Mother',
                'course_id' => $course->id,
                'applying_year' => now()->format('Y'),
                'applying_term' => 'Term 1',
                'applying_level' => strtoupper((string) $course->name),
                'physical_address' => rand(1, 120).' '.collect(['Borrowdale Road', 'Enterprise Road', 'Kuwadzana'])->random().', Harare',
                'phone' => '+263 78 '.rand(100000, 999999),
                'status' => $status,
                'documents_verified' => $status !== 'pending',
                'interview_status' => $status === 'pending' ? 'scheduled' : 'completed',
                'interview_date' => now()->addDays(rand(2, 20)),
                'decision_notes' => $status === 'rejected' ? null : 'Offered a place pending payment of registration fees.',
            ]);
            $track('applications', [$app->id]);

            // Attach a scanned birth certificate so the document registry looks lived-in.
            $documentType = array_key_first(ApplicationDocument::$documentTypes);
            $doc = ApplicationDocument::create([
                'school_id' => $schoolId,
                'application_id' => $app->id,
                'document_type' => $documentType,
                'title' => $documentType.' — '.$app->first_name.' '.$app->last_name,
                'file_path' => 'documents/demo/'.$schoolId.'/'.$app->application_number.'-'.str_replace('_', '-', (string) $documentType).'.pdf',
                'original_name' => str_replace('_', '-', (string) $documentType).'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => rand(80, 900),
            ]);
            $track('application_documents', [$doc->id]);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Operations
    // ────────────────────────────────────────────────────────────────────

    protected function seedFinance(int $schoolId, $year, $term, array $studentIdsList, ?int $actorId, callable $track, callable $created): void
    {
        $feeCategories = [];
        foreach ([
            ['Tuition Fees', 'Core academic tuition per term'],
            ['Registration Fees', 'One-off annual registration charge'],
            ['Sports & Culture', 'Sports kits, fixtures and cultural events'],
        ] as [$name, $desc]) {
            $feeCategories[] = $created(FeeCategory::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['description' => $desc]
            ));
        }

        $tuitionStructure = FeeStructure::where('school_id', $schoolId)
            ->where('fee_category_id', $feeCategories[0]->id)
            ->first();
        if (! $tuitionStructure) {
            $tuitionStructure = $created(FeeStructure::create([
                'school_id' => $schoolId,
                'fee_category_id' => $feeCategories[0]->id,
                'scope_type' => 'all',
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'currency' => 'USD',
                'amount' => 220.00,
            ]));
        }

        $regStructure = FeeStructure::where('school_id', $schoolId)
            ->where('fee_category_id', $feeCategories[1]->id)
            ->first();
        if (! $regStructure) {
            $regStructure = $created(FeeStructure::create([
                'school_id' => $schoolId,
                'fee_category_id' => $feeCategories[1]->id,
                'scope_type' => 'all',
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'currency' => 'USD',
                'amount' => 40.00,
            ]));
        }

        $invoiceSeq = Invoice::withoutGlobalScopes()->where('school_id', $schoolId)
            ->where('invoice_number', 'LIKE', 'TEST-INV-%')->count();

        $studentsForInvoices = Student::withoutGlobalScopes()->whereIn('id', $studentIdsList)->get();
        foreach ($studentsForInvoices as $student) {
            $invoiceSeq++;
            $subtotal = 260.00;
            $paid = collect([0, 0, 130.00, 260.00])->random();

            $invoice = Invoice::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'invoice_number' => 'TEST-INV-'.$schoolId.'-'.str_pad((string) $invoiceSeq, 5, '0', STR_PAD_LEFT),
                'currency' => 'USD',
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'paid_amount' => $paid,
                'balance_amount' => max(0, $subtotal - $paid),
                'status' => $paid >= $subtotal ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'due_date' => now()->addDays(rand(-20, 30))->toDateString(),
            ]);
            $track('invoices', [$invoice->id]);

            $item1 = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_structure_id' => $tuitionStructure->id,
                'name' => 'Tuition Fees — '.$term->name,
                'amount' => 220.00,
            ]);
            $track('invoice_items', [$item1->id]);
            $item2 = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_structure_id' => $regStructure->id,
                'name' => 'Registration Fees',
                'amount' => 40.00,
            ]);
            $track('invoice_items', [$item2->id]);

            if ($paid > 0) {
                $payment = Payment::create([
                    'school_id' => $schoolId,
                    'invoice_id' => $invoice->id,
                    'amount' => $paid,
                    'currency' => 'USD',
                    'payment_method' => collect(['cash', 'bank_transfer', 'Ecocash', 'zipit'])->random(),
                    'reference_number' => 'TEST-PAY-'.$invoice->invoice_number,
                    'receipt_number' => 'TEST-RCP-'.str_pad((string) rand(1000, 9999), 6, '0', STR_PAD_LEFT),
                    'payment_date' => now()->subDays(rand(1, 30))->toDateString(),
                ]);
                $track('payments', [$payment->id]);
            }
        }

        $supplierRows = [
            ['Harare Stationery Suppliers', 'Tendai Chikafu', 'sales@hararestationery.demo', '+263 24 700001'],
            ['Bright Future Textiles', 'Memory Ndoro', 'orders@brightfuture.demo', '+263 24 700002'],
            ['TechServe ICT Solutions', 'Blessing Gara', 'support@techserve.demo', '+263 24 700003'],
        ];
        $supplierIds = [];
        foreach ($supplierRows as $n => [$name, $person, $email, $phone]) {
            $supplier = $created(Supplier::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['contact_person' => $person, 'email' => $email, 'phone' => $phone, 'address' => 'Harare, Zimbabwe']
            ));
            $supplierIds[] = $supplier->id;
        }

        $expenseCategories = [
            'Operational Expenses' => 'Day-to-day running costs',
            'Teaching & Learning' => 'Books, stationery and learning aids',
            'Utilities & Maintenance' => 'Electricity, water, repairs and upkeep',
            'Administration' => 'Office supplies and communication',
        ];

        $categoryObjects = [];
        foreach ($expenseCategories as $catName => $desc) {
            $cat = $created(ExpenseCategory::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $catName],
                ['description' => $desc]
            ));
            $categoryObjects[$catName] = $cat;
        }

        $expenseType = $created(ExpenseType::firstOrCreate(
            ['school_id' => $schoolId, 'expense_category_id' => $categoryObjects['Operational Expenses']->id, 'name' => 'Stationery & Printing']
        ));

        $sampleExpenses = [
            ['Classroom Stationery Pack', 'Operational Expenses'],
            ['Textbook Replacements', 'Teaching & Learning'],
            ['Electricity & Water Bill', 'Utilities & Maintenance'],
            ['Laboratory Chemicals', 'Teaching & Learning'],
            ['Sports Equipment', 'Operational Expenses'],
            ['Internet & Fibre Subscription', 'Administration'],
            ['Roof Repairs & Maintenance', 'Utilities & Maintenance'],
            ['Printer Toner & Paper', 'Administration'],
        ];

        foreach ($sampleExpenses as $idx => [$expName, $catName]) {
            $cat = $categoryObjects[$catName] ?? $categoryObjects['Operational Expenses'];
            $expense = Expense::create([
                'school_id' => $schoolId,
                'expense_category_id' => $cat->id,
                'expense_type_id' => $expenseType->id,
                'expense_name' => $expName,
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'amount' => rand(45, 850),
                'expense_date' => now()->subDays(rand(1, 60))->toDateString(),
                'reference_number' => 'TEST-EXP-'.$schoolId.'-'.str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'notes' => 'Demonstration expense entry with category and name.',
                'status' => collect(['approved', 'paid'])->random(),
                'user_id' => $actorId,
            ]);
            $track('expenses', [$expense->id]);
        }
    }

    protected function seedLeaveAndPayroll(int $schoolId, ?int $actorId, array $staffUserIds, callable $track, callable $created): void
    {
        foreach ([
            ['Annual Leave', 'ANL', 21, true],
            ['Sick Leave', 'SCK', 12, false],
            ['Family Responsibility Leave', 'FAM', 5, false],
            ['Maternity Leave', 'MAT', 90, false],
            ['Study Leave', 'STD', 14, false],
        ] as [$name, $code, $days, $carry]) {
            if (LeaveType::where('school_id', $schoolId)->where('code', $code)->exists()) {
                continue;
            }
            $type = LeaveType::create([
                'school_id' => $schoolId,
                'name' => $name,
                'code' => $code,
                'days_per_year' => $days,
                'carry_forward' => $carry,
                'max_accumulation' => 30,
            ]);
            $track('leave_types', [$type->id]);
        }

        $allStaffEmails = Employee::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('email', 'LIKE', '%@demo.schoolcore.test')
            ->pluck('email', 'id');

        $leaveReasons = [
            ['ANL', 'Family visit to Mutare', 'approved'],
            ['ANL', 'End-of-year holiday', 'approved'],
            ['SCK', 'Recovering from flu', 'approved'],
            ['FAM', 'Attending a funeral in Masvingo', 'approved'],
            ['SCK', 'Medical appointment', 'pending'],
            ['STD', 'Workshop on continuous assessment', 'pending'],
            ['ANL', 'Wedding preparations', 'pending'],
            ['MAT', 'Maternity leave', 'approved'],
        ];

        $employeeIds = $allStaffEmails->keys()->all();
        $leaveTypeByCode = LeaveType::where('school_id', $schoolId)->get()->keyBy('code');

        foreach ($leaveReasons as $i => [$code, $reason, $status]) {
            $employeeId = $employeeIds[$i % count($employeeIds)];
            $start = now()->subMonths(rand(1, 5))->startOfMonth()->addDays(rand(1, 15))->toDateString();
            $end = now()->subMonths(rand(1, 5))->startOfMonth()->addDays(rand(3, 20))->toDateString();

            $request = LeaveRequest::create([
                'school_id' => $schoolId,
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeByCode[$code]->id,
                'start_date' => $start,
                'end_date' => $end,
                'reason' => $reason,
                'status' => $status,
                'hr_remarks' => $status === 'approved' ? 'Approved by HR.' : null,
                'approved_by_id' => $status === 'approved' ? ($actorId ?? null) : null,
            ]);
            $track('leave_requests', [$request->id]);
        }

        // Payroll period + run + payslips + items.
        $periodStart = now()->subMonth()->startOfMonth();
        $periodExists = PayrollPeriod::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('start_date', $periodStart->toDateString())
            ->first();
        if ($periodExists) {
            $period = $periodExists;
        } else {
            $period = PayrollPeriod::create([
                'school_id' => $schoolId,
                'name' => 'Payroll '.$periodStart->format('F Y'),
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodStart->copy()->endOfMonth()->toDateString(),
                'status' => 'processed',
            ]);
            $track('payroll_periods', [$period->id]);
        }

        $run = PayrollRun::create([
            'school_id' => $schoolId,
            'payroll_period_id' => $period->id,
            'status' => 'released',
            'calculated_at' => now()->subDays(5),
            'approved_at' => now()->subDays(3),
            'released_at' => now()->subDays(2),
            'gross_total' => 0,
            'deductions_total' => 0,
            'net_total' => 0,
        ]);
        $track('payroll_runs', [$run->id]);

        $grades = SalaryGrade::where('school_id', $schoolId)->get()->keyBy('id');
        $grossTotal = 0;
        $deductionTotal = 0;
        $netTotal = 0;

        $employees = Employee::withoutGlobalScopes()->where('school_id', $schoolId)->whereIn('id', $employeeIds)->get();
        foreach ($employees as $employee) {
            $grade = $grades->get($employee->current_grade_id);
            $base = (float) ($grade->base_salary ?? 650);
            $housing = (float) ($grade->housing_allowance ?? 0);
            $transport = (float) ($grade->transport_allowance ?? 0);
            $duty = (float) ($grade->duty_allowance ?? 0);
            $gross = $base + $housing + $transport + $duty;
            $deductions = round($gross * 0.085, 2); // PAYE + NSSA
            $net = round($gross - $deductions, 2);

            $grossTotal += $gross;
            $deductionTotal += $deductions;
            $netTotal += $net;

            $payslip = Payslip::create([
                'school_id' => $schoolId,
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'base_salary' => $base,
                'gross_pay' => $gross,
                'total_deductions' => $deductions,
                'net_pay' => $net,
                'status' => 'paid',
                'payment_method' => 'Bank Transfer',
                'payment_date' => now()->subDay()->toDateString(),
                'transaction_reference' => 'TX-DEMO-'.random_int(100000, 999999),
                'integrity_hash' => hash_hmac('sha256', $period->id.$employee->id.$gross, config('app.key')),
            ]);
            $track('payslips', [$payslip->id]);

            foreach ([
                ['BAS', 'Basic Salary', 'earning', $base, true],
                ['HOU', 'Housing Allowance', 'earning', $housing, true],
                ['TRN', 'Transport Allowance', 'earning', $transport, true],
                ['DUT', 'Duty Allowance', 'earning', $duty, true],
                ['PAY', 'PAYE Tax', 'deduction', round($gross * 0.07, 2), false],
                ['NSS', 'NSSA Contribution', 'deduction', round($gross * 0.015, 2), false],
            ] as [$code, $name, $type, $amount, $taxable]) {
                if ($type === 'earning' && $amount <= 0) {
                    continue;
                }
                $item = PayslipItem::create([
                    'school_id' => $schoolId,
                    'payslip_id' => $payslip->id,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'amount' => round($amount, 2),
                    'is_taxable' => $taxable,
                    'is_recurring' => true,
                ]);
                $track('payslip_items', [$item->id]);
            }
        }

        $run->update([
            'gross_total' => round($grossTotal, 2),
            'deductions_total' => round($deductionTotal, 2),
            'net_total' => round($netTotal, 2),
        ]);

        // Salary grade progression history for the deputy head.
        $deputy = Employee::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('email', 'petros.ngwenya@demo.schoolcore.test')
            ->first();
        if ($deputy && $actorId) {
            $oldGradeId = $gradeIdsFallback = null;
            $oldGrade = SalaryGrade::withoutGlobalScopes()->where('school_id', $schoolId)->where('name', 'T2 — Teacher')->first();
            if ($oldGrade && (int) $deputy->current_grade_id !== (int) $oldGrade->id) {
                $history = SalaryGradeHistory::create([
                    'school_id' => $schoolId,
                    'employee_id' => $deputy->id,
                    'previous_grade_id' => $oldGrade->id,
                    'new_grade_id' => $deputy->current_grade_id,
                    'base_salary' => (float) ($grades->get($deputy->current_grade_id)->base_salary ?? 0),
                    'effective_date' => now()->subMonths(6)->toDateString(),
                    'reason' => 'Annual performance-based promotion',
                    'approved_by_id' => $actorId,
                ]);
                $track('salary_grade_history', [$history->id]);
            }
        }

        // Staff attendance: last 10 school weekdays for every demo employee user.
        $weekdays = $this->recentWeekdays(10);
        foreach ($employees as $employee) {
            if (! $employee->user_id) {
                continue;
            }
            foreach ($weekdays as $date) {
                $status = collect(['present', 'present', 'present', 'present', 'late', 'absent'])->random();
                if (StaffAttendance::where('school_id', $schoolId)->where('user_id', $employee->user_id)->where('date', $date)->exists()) {
                    continue;
                }
                $att = StaffAttendance::create([
                    'school_id' => $schoolId,
                    'user_id' => $employee->user_id,
                    'date' => $date,
                    'status' => $status,
                    'check_in_time' => '07:55',
                    'check_out_time' => '15:45',
                    'method' => 'manual',
                    'marked_by_id' => $actorId,
                ]);
                $track('staff_attendances', [$att->id]);
            }
        }
    }

    protected function seedInventoryAndProcurement(int $schoolId, ?int $actorId, array $staffUserIds, callable $track, callable $created): void
    {
        $invCats = [];
        foreach ([['Stationery', 'Paper, pens and office supplies'], ['Cleaning Materials', 'Janitorial consumables'], ['ICT Equipment', 'Computers and peripherals'], ['Science Equipment', 'Laboratory apparatus']] as [$name, $desc]) {
            $invCats[] = $created(InventoryCategory::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['description' => $desc]
            ));
        }

        $itemSpecs = [
            [0, 'A4 Ream Paper', 'consumable', 'ream', 40, 250, 4.50],
            [0, 'Whiteboard Markers (box)', 'consumable', 'box', 15, 90, 7.00],
            [1, 'Disinfectant 5L', 'consumable', 'bottle', 10, 60, 9.50],
            [1, 'Broom (hard bristle)', 'consumable', 'piece', 8, 35, 5.00],
            [2, 'Projector XGA', 'fixed_asset', 'unit', 2, 8, 420.00],
            [2, 'Laptop — Staff', 'fixed_asset', 'unit', 1, 12, 680.00],
            [0, 'Chalk (box)', 'consumable', 'box', 12, 140, 2.80],
            [2, 'Network Switch 24-port', 'fixed_asset', 'unit', 1, 4, 190.00],
            [3, 'Microscope Kit', 'fixed_asset', 'unit', 1, 6, 540.00],
            [3, 'Bunsen Burners (set)', 'consumable', 'set', 5, 30, 42.00],
        ];
        $itemIds = [];
        foreach ($itemSpecs as $n => [$catIdx, $name, $type, $uom, $reorder, $qty, $cost]) {
            $sku = 'TEST-SKU-'.$schoolId.'-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT);
            $item = InventoryItem::withoutGlobalScopes()->where('school_id', $schoolId)->where('sku', $sku)->first();
            if (! $item) {
                $item = $created(InventoryItem::create([
                    'school_id' => $schoolId,
                    'category_id' => $invCats[$catIdx]->id,
                    'sku' => $sku,
                    'name' => $name,
                    'item_type' => $type,
                    'unit_of_measure' => $uom,
                    'reorder_level' => $reorder,
                    'current_quantity' => $qty,
                    'average_unit_cost' => $cost,
                    'is_saleable' => false,
                ]));
            }
            $itemIds[] = $item->id;
        }

        // Inventory locations + suppliers.
        foreach ([
            ['Main Stores', 'STO-MAIN', 'general'],
            ['ICT Room', 'LOC-ICT', 'ict'],
            ['Science Laboratory', 'LOC-LAB', 'laboratory'],
            ['Sports Pavilion', 'LOC-SPT', 'sports'],
        ] as [$name, $code, $type]) {
            if (InventoryLocation::where('school_id', $schoolId)->where('code', $code)->exists()) {
                continue;
            }
            $loc = InventoryLocation::create([
                'school_id' => $schoolId,
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'temperature_sensitive' => $code === 'LOC-LAB',
            ]);
            $track('inventory_locations', [$loc->id]);
        }

        $supplierIds = [];
        foreach ([
            ['Harare Stationery Suppliers', 'Tendai Chikafu', '+263 24 700001', 'sales@hararestationery.demo', '19 Sam Nujoma Street, Harare'],
            ['Zimbabwe Laboratory Supplies', 'Nyaradzo Gumbo', '+263 24 700004', 'orders@zimsci.demo', '24 Lobengula Avenue, Bulawayo'],
            ['TechServe ICT Solutions', 'Blessing Gara', '+263 24 700003', 'support@techserve.demo', '8 Josiah Tongogara Avenue, Harare'],
        ] as [$name, $person, $phone, $email, $address]) {
            if (InventorySupplier::where('school_id', $schoolId)->where('name', $name)->exists()) {
                continue;
            }
            $sup = InventorySupplier::create([
                'school_id' => $schoolId,
                'name' => $name,
                'contact_person' => $person,
                'phone' => $phone,
                'email' => $email,
                'physical_address' => $address,
            ]);
            $track('inventory_suppliers', [$sup->id]);
            $supplierIds[] = $sup->id;
        }

        // Fixed assets with maintenance logs.
        $assetEligible = InventoryItem::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('id', $itemIds)
            ->where('item_type', 'fixed_asset')
            ->get();
        foreach ($assetEligible as $n => $item) {
            $asset = FixedAsset::withoutGlobalScopes()->where('school_id', $schoolId)->where('inventory_item_id', $item->id)->first();
            if (! $asset) {
                $asset = FixedAsset::create([
                    'school_id' => $schoolId,
                    'inventory_item_id' => $item->id,
                    'asset_number' => 'TEST-AST-'.$schoolId.'-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                    'serial_number' => 'SN-DEMO-'.strtoupper(Str::random(8)),
                    'acquisition_date' => now()->subYears(rand(1, 4))->toDateString(),
                    'purchase_cost' => $item->average_unit_cost,
                    'salvage_value' => round($item->average_unit_cost * 0.1, 2),
                    'useful_life_years' => 5,
                    'depreciation_method' => 'straight_line',
                    'current_value' => round($item->average_unit_cost * rand(60, 90) / 100, 2),
                    'funding_source' => 'School Development Fund',
                    'status' => 'in_use',
                ]);
                $track('fixed_assets', [$asset->id]);
            }

            $maintenance = AssetMaintenanceLog::create([
                'school_id' => $schoolId,
                'fixed_asset_id' => $asset->id,
                'title' => 'Routine service — '.$item->name,
                'type' => 'preventive',
                'schedule_type' => 'one_time',
                'scheduled_date' => now()->subDays(rand(10, 60))->toDateString(),
                'completed_date' => now()->subDays(rand(1, 30))->toDateString(),
                'cost' => rand(15, 90),
                'performed_by' => 'External technician',
                'status' => 'completed',
                'notes' => 'Demo maintenance job.',
            ]);
            $track('asset_maintenance_logs', [$maintenance->id]);
        }

        // Procurement requests + orders.
        $procurementSpecs = [
            ['replenishment', 'Restock stationery ahead of new term', 2],
            ['capital_purchase', 'Second batch of science microscopes', 2],
            ['replenishment', 'ICT equipment refresh for computer lab', 2],
        ];
        $requesterId = $staffUserIds['non_teaching_staff'][0] ?? $actorId;
        foreach ($procurementSpecs as $i => [$urgency, $notes, $itemCount]) {
            $reqNumber = 'TEST-PREQ-'.$schoolId.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $existingReq = ProcurementRequest::withoutGlobalScopes()->where('school_id', $schoolId)->where('request_number', $reqNumber)->first();
            if ($existingReq) {
                continue;
            }

            $request = ProcurementRequest::create([
                'school_id' => $schoolId,
                'request_number' => $reqNumber,
                'requester_id' => $requesterId,
                'status' => 'approved',
                'urgency' => $urgency,
                'notes' => $notes,
                'purpose' => 'Term start preparation',
            ]);
            $track('procurement_requests', [$request->id]);

            for ($j = 0; $j < $itemCount; $j++) {
                $item = InventoryItem::withoutGlobalScopes()->whereIn('id', $itemIds)->get()[$j % count($itemIds)];
                $reqItem = ProcurementRequestItem::create([
                    'procurement_request_id' => $request->id,
                    'item_name' => $item->name,
                    'inventory_item_id' => $item->id,
                    'quantity' => rand(2, 20),
                    'estimated_unit_cost' => $item->average_unit_cost,
                    'specifications' => null,
                ]);
                $track('procurement_request_items', [$reqItem->id]);
            }

            $orderNumber = 'TEST-PO-'.$schoolId.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $order = ProcurementOrder::create([
                'school_id' => $schoolId,
                'procurement_request_id' => $request->id,
                'supplier_id' => $supplierIds[$i % max(1, count($supplierIds))],
                'order_number' => $orderNumber,
                'order_date' => now()->subDays(rand(5, 30))->toDateString(),
                'expected_delivery_date' => now()->addDays(rand(3, 14))->toDateString(),
                'status' => 'delivered',
                'total_amount' => 0,
            ]);
            $track('procurement_orders', [$order->id]);

            $orderItems = ProcurementRequest::withoutGlobalScopes()->find($request->id)
                ->items()->get();
            $total = 0;
            foreach ($orderItems as $reqItem) {
                $orderItem = ProcurementOrderItem::create([
                    'procurement_order_id' => $order->id,
                    'inventory_item_id' => $reqItem->inventory_item_id,
                    'quantity_ordered' => $reqItem->quantity,
                    'quantity_received' => $reqItem->quantity,
                    'unit_cost' => $reqItem->estimated_unit_cost,
                ]);
                $track('procurement_order_items', [$orderItem->id]);
                $total += $reqItem->quantity * $reqItem->estimated_unit_cost;
            }
            $order->update(['total_amount' => round($total, 2)]);
        }
    }

    protected function seedLibraryAndKnowledge(int $schoolId, ?int $actorId, array $studentIdsList, array $staffUserIds, callable $track, callable $created): void
    {
        $libCatFiction = $created(LibraryCategory::firstOrCreate(['school_id' => $schoolId, 'name' => 'Fiction']));
        $libCatReference = $created(LibraryCategory::firstOrCreate(['school_id' => $schoolId, 'name' => 'Reference']));
        $libCatTextbook = $created(LibraryCategory::firstOrCreate(['school_id' => $schoolId, 'name' => 'Textbooks']));
        $fmtPrint = $created(LibraryFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'Print Book'], ['media_type' => 'physical']));
        $fmtEbook = $created(LibraryFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'E-Book'], ['media_type' => 'digital']));

        $authorSpecs = [
            ['Wayne Maphosa', 'Zimbabwean novelist and playwright.'],
            ['Tsitsi Dangarembga', 'Author of the acclaimed "Nervous Conditions".'],
            ['Petina Gappah', 'Zimbabwean short-story writer and novelist.'],
            ['J. Sadler', 'Author of revision textbooks for secondary mathematics.'],
            ['Doris Lessing', 'Zimbabwe-born Nobel laureate in literature.'],
            ['FreeTech Press', 'Published educational and technical materials.'],
        ];
        $authorIds = [];
        foreach ($authorSpecs as [$name, $bio]) {
            $author = LibraryAuthor::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['bio' => $bio]
            );
            $authorIds[$name] = $author->id;
            $created($author);
        }

        $bookSpecs = [
            [$libCatFiction->id, $fmtPrint->id, 'The House of Hunger', 'Wayne Maphosa', 1978, 'Fiction', 3],
            [$libCatFiction->id, $fmtPrint->id, 'Nervous Conditions', 'Tsitsi Dangarembga', 1988, 'Fiction', 2],
            [$libCatFiction->id, $fmtEbook->id, 'An Elegy for Easterly', 'Petina Gappah', 2009, 'Fiction', 2],
            [$libCatReference->id, $fmtPrint->id, 'O-Level Mathematics Revision', 'J. Sadler', 2015, 'Mathematics', 4],
            [$libCatReference->id, $fmtPrint->id, 'Atlas of Southern Africa', 'FreeTech Press', 2012, 'Geography', 1],
            [$libCatTextbook->id, $fmtEbook->id, 'Introduction to Programming', 'FreeTech Press', 2020, 'Computer Science', 2],
            [$libCatTextbook->id, $fmtPrint->id, 'The Grass is Singing', 'Doris Lessing', 1950, 'Literature', 2],
            [$libCatReference->id, $fmtPrint->id, 'Physical Science Activity Book', 'FreeTech Press', 2018, 'Combined Science', 3],
        ];

        $bookCopyIds = [];
        foreach ($bookSpecs as $n => [$categoryId, $formatId, $title, $authorName, $pubYear, $subjectName, $copies]) {
            $isbn = 'TEST-ISBN-'.$schoolId.'-'.str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);
            $book = LibraryBook::withoutGlobalScopes()->where('school_id', $schoolId)->where('isbn', $isbn)->first();
            if (! $book) {
                $book = LibraryBook::create([
                    'school_id' => $schoolId,
                    'library_category_id' => $categoryId,
                    'library_format_id' => $formatId,
                    'title' => $title,
                    'publisher' => 'Demo Press',
                    'publication_year' => (string) $pubYear,
                    'isbn' => $isbn,
                    'language' => 'English',
                    'subject' => $subjectName,
                    'media_type' => $formatId === $fmtEbook->id ? 'digital' : 'physical',
                    'description' => 'Demonstration library title for testing circulation.',
                ]);
                $track('library_books', [$book->id]);
            }

            $pivot = DB::table('library_book_author')
                ->where('library_book_id', $book->id)
                ->where('library_author_id', $authorIds[$authorName])
                ->first();
            if (! $pivot) {
                $pivotId = DB::table('library_book_author')->insertGetId([
                    'library_book_id' => $book->id,
                    'library_author_id' => $authorIds[$authorName],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $manifestRef = null;
                $track('library_book_author', [$pivotId]);
            }

            if ($formatId !== $fmtEbook->id) {
                for ($c = 1; $c <= $copies; $c++) {
                    $barcode = 'TEST-BC-'.$schoolId.'-'.str_pad($book->id, 4, '0', STR_PAD_LEFT).'-'.$c;
                    if (LibraryBookCopy::where('school_id', $schoolId)->where('barcode', $barcode)->exists()) {
                        continue;
                    }
                    $copy = LibraryBookCopy::create([
                        'school_id' => $schoolId,
                        'library_book_id' => $book->id,
                        'barcode' => $barcode,
                        'qr_code' => 'QR-'.hash('crc32b', $barcode),
                        'shelf' => ['S1', 'S2', 'R1'][$n % 3],
                        'rack' => (string) (($n % 4) + 1),
                        'position' => (string) (($c % 3) + 1),
                        'condition' => 'good',
                        'status' => 'available',
                        'purchase_cost' => round(rand(600, 2400) / 100, 2),
                        'replacement_cost' => round(rand(800, 3000) / 100, 2),
                        'acquired_date' => now()->subYears(rand(0, 3))->toDateString(),
                    ]);
                    $track('library_book_copies', [$copy->id]);
                    $bookCopyIds[] = $copy->id;
                }
            }
        }

        // Circulation: issue a handful of physical copies to students.
        $students = Student::withoutGlobalScopes()->whereIn('id', $studentIdsList)->get();
        $librarianUserId = $staffUserIds['non_teaching_staff'][5] ?? $staffUserIds['non_teaching_staff'][0] ?? $actorId;
        $issueStatuses = [
            ['issued', null],
            ['returned', true],
            ['issued', null],
            ['returned', true],
            ['overdue', null],
        ];
        foreach (array_slice($bookCopyIds, 0, 5) as $i => $copyId) {
            [$status, $returned] = $issueStatuses[$i % count($issueStatuses)];
            $student = $students[$i % max(1, $students->count())];
            $issue = LibraryIssue::create([
                'school_id' => $schoolId,
                'library_book_copy_id' => $copyId,
                'student_id' => $student->id,
                'issued_by_id' => $librarianUserId,
                'issued_at' => now()->subDays(rand(5, 25))->toDateString(),
                'due_at' => now()->addDays(rand(3, 16))->toDateString(),
                'returned_at' => $returned ? now()->subDays(rand(1, 5))->toDateString() : null,
                'status' => $status,
                'fine_amount' => $status === 'overdue' ? 1.5 : 0,
                'fine_status' => $status === 'overdue' ? 'unpaid' : 'waived',
                'renewals_count' => 0,
                'notes' => 'Demonstration circulation record.',
            ]);
            $track('library_issues', [$issue->id]);
        }

        // Knowledge repository.
        $fmtPrinted = $created(KnowledgeFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'Printed Set'], ['media_type' => 'physical']));
        $fmtDigital = $created(KnowledgeFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'Digital'], ['media_type' => 'digital']));

        $assetSpecs = [
            [$libCatReference->id, $fmtPrinted->id, 'Form 3 Science Revision Notes', 'physical', 'FreeTech Press', 2021],
            [$libCatReference->id, $fmtPrinted->id, 'O-Level Mathematics Past Papers', 'physical', 'Demo Press', 2022],
            [$libCatReference->id, $fmtDigital->id, 'Campus ICT Handbook', 'digital', 'FreeTech Press', 2023],
            [$libCatReference->id, $fmtDigital->id, 'Heritage Studies Resource Pack', 'digital', 'Demo Press', 2024],
        ];
        foreach ($assetSpecs as $n => [$categoryId, $formatId, $title, $mediaType, $publisher, $pubYear]) {
            $isbn = 'TEST-KA-'.$schoolId.'-'.str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);
            if (KnowledgeAsset::withoutGlobalScopes()->where('school_id', $schoolId)->where('isbn', $isbn)->exists()) {
                continue;
            }

            $asset = KnowledgeAsset::create([
                'school_id' => $schoolId,
                'uploaded_by_id' => $actorId ?? 1,
                'library_category_id' => $categoryId,
                'knowledge_format_id' => $formatId,
                'title' => $title,
                'subtitle' => 'Repository demonstration asset',
                'subtype' => null,
                'abstract_description' => 'Sample knowledge repository asset used to exercise the repository module.',
                'visibility' => 'library_only',
                'isbn' => $isbn,
                'publisher' => $publisher,
                'publication_year' => (string) $pubYear,
                'language' => 'English',
                'media_type' => $mediaType,
                'file_path' => $mediaType === 'digital' ? 'knowledge/demo/'.$schoolId.'/'.Str::slug($title).'.pdf' : null,
            ]);
            $track('knowledge_assets', [$asset->id]);

            $authorPivot = DB::table('knowledge_asset_author')
                ->where('knowledge_asset_id', $asset->id)
                ->where('library_author_id', $authorIds['FreeTech Press'])
                ->first();
            if (! $authorPivot) {
                $authPivotId = DB::table('knowledge_asset_author')->insertGetId([
                    'knowledge_asset_id' => $asset->id,
                    'library_author_id' => $authorIds['FreeTech Press'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $track('knowledge_asset_author', [$authPivotId]);
            }

            if ($mediaType === 'physical') {
                $barcode = 'TEST-KB-'.$schoolId.'-'.str_pad($asset->id, 4, '0', STR_PAD_LEFT);
                if (! KnowledgeAssetCopy::where('school_id', $schoolId)->where('barcode', $barcode)->exists()) {
                    $copy = KnowledgeAssetCopy::create([
                        'school_id' => $schoolId,
                        'knowledge_asset_id' => $asset->id,
                        'barcode' => $barcode,
                        'qr_code' => 'KLQR-'.hash('crc32b', $barcode),
                        'shelf' => 'K'.(($n % 3) + 1),
                        'condition' => 'good',
                        'status' => 'available',
                    ]);
                    $track('knowledge_asset_copies', [$copy->id]);
                }
            }
        }
    }

    protected function seedClinic(int $schoolId, array $studentIdsList, array $bloodGroups, ?int $actorId, callable $track, callable $created): void
    {
        $clinicStudents = Student::withoutGlobalScopes()->whereIn('id', $studentIdsList)->take(8)->get();
        foreach ($clinicStudents as $n => $student) {
            $hasRecord = StudentMedicalRecord::withoutGlobalScopes()
                ->where('school_id', $schoolId)->where('student_id', $student->id)->exists();
            if ($hasRecord) {
                continue;
            }

            $record = StudentMedicalRecord::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'blood_group' => $bloodGroups[rand(0, 4)],
                'allergies' => $n % 3 === 0 ? 'Penicillin' : 'None known',
                'chronic_conditions' => $n % 4 === 0 ? 'Mild asthma' : 'None',
                'immunization_history' => ['BCG' => 'Complete', 'Polio' => 'Complete', 'Measles' => 'Booster given'],
                'regular_medications' => $n % 4 === 0 ? 'Salbutamol inhaler (as needed)' : 'None',
            ]);
            $track('student_medical_records', [$record->id]);
        }

        $symptomsPool = [
            ['Headache, mild fever', 'Seasonal flu', 'Paracetamol, rest observed'],
            ['Stomach cramps', 'Indigestion', 'Antacid administered'],
            ['Graised knee after fall', 'Minor abrasion', 'Wound cleaned and dressed'],
            ['Sore throat', 'Upper respiratory infection', 'Warm saline gargle advised'],
        ];
        foreach ($clinicStudents->take(5) as $n => $student) {
            [$symptoms, $diagnosis, $treatment] = $symptomsPool[$n % count($symptomsPool)];

            $visit = ClinicVisit::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'recorded_by_user_id' => $actorId,
                'visit_time' => now()->subDays(rand(1, 21))->setTime(rand(8, 13), rand(0, 59)),
                'symptoms' => $symptoms,
                'diagnosis' => $diagnosis,
                'treatment_given' => $treatment,
                'temperature_celsius' => rand(360, 385) / 10,
                'status' => 'discharged',
            ]);
            $track('clinic_visits', [$visit->id]);
        }
    }

    protected function seedHostels(int $schoolId, $year, array $studentIdsList, callable $track, callable $created): void
    {
        $hostelSpecs = [
            ['Boys Hostel — Nyanga House', 'boys'],
            ['Girls Hostel — Chiadzwa House', 'girls'],
        ];

        foreach ($hostelSpecs as $hi => [$hostelName, $hostelType]) {
            $hostel = Hostel::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $hostelName],
                ['type' => $hostelType, 'capacity' => 32, 'status' => 'operational', 'description' => 'Demonstration boarding house.']
            );
            $created($hostel);

            foreach (['Block A', 'Block B'] as $bi => $blockName) {
                $building = HostelBuilding::firstOrCreate(
                    ['school_id' => $schoolId, 'hostel_id' => $hostel->id, 'name' => $blockName],
                    ['description' => $bi === 0 ? 'Ground floor block.' : 'Upper floor block.']
                );
                $created($building);

                for ($f = 1; $f <= 2; $f++) {
                    $floor = HostelFloor::firstOrCreate(
                        ['school_id' => $schoolId, 'hostel_id' => $hostel->id, 'floor_number' => $f],
                        ['floor_name' => $f === 1 ? 'Ground Floor' : 'First Floor'.' '.$blockName]
                    );
                    $created($floor);

                    $wing = HostelWing::firstOrCreate(
                        ['school_id' => $schoolId, 'floor_id' => $floor->id, 'name' => [0 => 'East Wing', 1 => 'West Wing'][$f % 2]]
                    );
                    $created($wing);

                    foreach ([1, 2] as $ri => $roomNo) {
                        $roomNumber = substr((string) $blockName, -1).$f.$roomNo;
                        $room = HostelRoom::firstOrCreate(
                            ['school_id' => $schoolId, 'hostel_id' => $hostel->id, 'room_number' => $roomNumber],
                            [
                                'wing_id' => $wing->id,
                                'floor_id' => $floor->id,
                                'name' => 'Room '.$roomNumber,
                                'room_type' => 'dormitory',
                                'condition' => 'good',
                                'status' => 'available',
                                'capacity' => 2,
                            ]
                        );
                        $created($room);

                        foreach ([1, 2] as $bedNo) {
                            $bedNumber = $roomNumber.'-B'.$bedNo;
                            $bed = HostelBed::firstOrCreate(
                                ['school_id' => $schoolId, 'room_id' => $room->id, 'bed_number' => $bedNumber],
                                ['condition' => 'good', 'status' => 'vacant', 'cleaning_status' => 'clean']
                            );
                            if ($bed->wasRecentlyCreated) {
                                $track('hostel_beds', [$bed->id]);
                            }
                        }
                    }
                }
            }
        }

        $boardersByGender = Student::withoutGlobalScopes()->whereIn('id', $studentIdsList)
            ->where('boarding_status', 'boarder')
            ->get()
            ->groupBy('gender');

        $genderToHostel = ['male' => 'Boys Hostel — Nyanga House', 'female' => 'Girls Hostel — Chiadzwa House'];

        foreach ($genderToHostel as $gender => $hostelName) {
            if ($gender === 'other') {
                continue;
            }
            $boarders = $boardersByGender->get($gender) ?? collect();
            if ($boarders->isEmpty()) {
                continue;
            }

            $hostel = Hostel::withoutGlobalScopes()->where('school_id', $schoolId)->where('name', $hostelName)->first();
            if (! $hostel) {
                continue;
            }

            $freeBeds = HostelBed::withoutGlobalScopes()
                ->whereHas('room', fn ($q) => $q->where('hostel_id', $hostel->id))
                ->where('status', 'vacant')
                ->get();

            foreach ($boarders->zip($freeBeds) as [$student, $bed]) {
                if (! $student || ! $bed) {
                    break;
                }

                $alreadyAllocated = HostelAllocation::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->where('status', 'active')
                    ->exists();
                if ($alreadyAllocated) {
                    continue;
                }

                $allocation = HostelAllocation::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'bed_id' => $bed->id,
                    'academic_year_id' => $year->id,
                    'status' => 'active',
                    'allocated_at' => now()->subDays(rand(5, 40)),
                    'notes' => 'Demonstration allocation.',
                ]);
                $track('hostel_allocations', [$allocation->id]);
            }
        }
    }

    protected function seedTimetableAndAttendance(int $schoolId, $year, $term, $sections, array $studentIdsList, array $staffUserIds, ?int $actorId, callable $track, callable $created): void
    {
        if (! $actorId) {
            return;
        }

        // Generate Lessons uses app('current_tenant'), so bind it when running
        // from the CLI/artisan or other non-HTTP context.
        if (! app()->bound('current_tenant')) {
            app()->instance('current_tenant', School::find($schoolId));
        }

        // One homeroom per stream plus the two purpose-built labs. The labs
        // stay selectable in the classroom editor but are never chosen by the
        // auto-placer (the school books them per lesson when it wants to).
        $homeroomRooms = [
            'Room 101 (ECD A A)' => 'ECD A A',
            'Room 102 (ECD A B)' => 'ECD A B',
            'Room 103 (ECD B A)' => 'ECD B A',
            'Room 104 (ECD B B)' => 'ECD B B',
            'Room 105 (Grade 1 A)' => 'Grade 1 A',
            'Room 106 (Grade 1 B)' => 'Grade 1 B',
            'Room 107 (Grade 2 A)' => 'Grade 2 A',
            'Room 108 (Grade 2 B)' => 'Grade 2 B',
            'Room 109 (Grade 3 A)' => 'Grade 3 A',
            'Room 110 (Grade 3 B)' => 'Grade 3 B',
            'Room 111 (Grade 4 A)' => 'Grade 4 A',
            'Room 112 (Grade 4 B)' => 'Grade 4 B',
            'Room 113 (Grade 5 A)' => 'Grade 5 A',
            'Room 114 (Grade 5 B)' => 'Grade 5 B',
            'Room 115 (Grade 6 A)' => 'Grade 6 A',
            'Room 116 (Grade 6 B)' => 'Grade 6 B',
            'Room 117 (Grade 7 A)' => 'Grade 7 A',
            'Room 118 (Grade 7 B)' => 'Grade 7 B',
            'Science Laboratory' => null,
            'Computer Laboratory' => null,
        ];

        $sectionByLabel = $sections->keyBy(fn ($s) => trim($s->course->name.' '.$s->name));

        foreach ($homeroomRooms as $roomName => $sectionLabel) {
            $classroom = Classroom::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $roomName],
                ['capacity' => 40, 'location' => 'Main Block']
            );
            $created($classroom);

            if (! $sectionLabel) {
                continue;
            }

            $section = $sectionByLabel->get($sectionLabel);
            if (! $section) {
                continue;
            }

            Section::withoutGlobalScopes()
                ->whereKey($section->id)
                ->update(['classroom_id' => $classroom->id]);
        }

        // Active template: "Summer Timetable" → 6 × 60-min periods (07:30-15:30),
        // Tea after Period 3 (30 min), Lunch after Period 5 (60 min) and a
        // closing Free/Buffer slot = exactly 30 teaching slots per stream.
        $settings = [
            'start_time' => '07:30',
            'end_time_of_lessons' => '15:30',
            'period_length' => 60,
            'has_fixed_break' => false,
            'fixed_break_time' => null,
            'break_duration' => 30,
            'break_after_period' => 3,
            'has_fixed_lunch' => false,
            'fixed_lunch_time' => null,
            'lunch_duration' => 60,
            'lunch_after_period' => 5,
        ];

        $template = TimetableTemplate::where('school_id', $schoolId)->where('name', 'Summer Timetable')->first();
        if (! $template) {
            $template = TimetableTemplate::create([
                'school_id' => $schoolId,
                'name' => 'Summer Timetable',
                'is_active' => true,
                'settings' => $settings,
            ]);
        } else {
            $template->update(['is_active' => true, 'settings' => $settings]);
        }

        $generator = app(TimetableGeneratorService::class);
        $generator->generate($settings, $template->id);

        $result = $generator->autoPlaceLessons([
            'template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'replace_unlocked' => true,
            'max_per_subject_per_day' => 1,
        ]);

        $sectionLessonIds = [];
        $lessons = TimetableLesson::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('template_id', $template->id)
            ->orderBy('section_id')
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get(['id', 'section_id']);

        foreach ($lessons as $lesson) {
            $sectionLessonIds[$lesson->section_id][] = $lesson->id;
        }

        $weekdays = $this->recentWeekdays(10);

        foreach ($sections as $section) {
            $lessonId = $sectionLessonIds[$section->id][0] ?? null;
            if (! $lessonId) {
                continue;
            }

            $sectionStudents = Student::withoutGlobalScopes()
                ->whereHas('enrollments', fn ($q) => $q->where('section_id', $section->id))
                ->whereIn('id', $studentIdsList)
                ->get();

            foreach ($sectionStudents as $student) {
                foreach ($weekdays as $date) {
                    $exists = StudentAttendance::withoutGlobalScopes()
                        ->where('school_id', $schoolId)
                        ->where('student_id', $student->id)
                        ->where('timetable_lesson_id', $lessonId)
                        ->where('date', $date)
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    $status = collect(['present', 'present', 'present', 'present', 'absent', 'late'])->random();
                    $row = new StudentAttendance([
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                        'timetable_lesson_id' => $lessonId,
                        'date' => $date,
                        'status' => $status,
                        'remarks' => $status === 'late' ? 'Arrived after assembly' : null,
                        'marked_by_id' => $actorId,
                    ]);
                    $row->save();
                    $track('student_attendances', [$row->id]);
                }
            }
        }
    }

    protected function seedHomework(int $schoolId, $sections, array $studentIdsList, callable $track, callable $created): void
    {
        foreach ($sections as $section) {
            if (! $section->course) {
                continue;
            }

            $attachedSubjects = DB::table('course_subject')
                ->where('school_id', $schoolId)
                ->where('course_id', $section->course->id)
                ->orderBy('id')
                ->limit(2)
                ->get();

            foreach ($attachedSubjects as $idx => $attached) {
                $existsHw = Homework::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('section_id', $section->id)
                    ->where('subject_id', $attached->subject_id)
                    ->where('title', 'LIKE', 'TEST-%')
                    ->exists();
                if ($existsHw) {
                    continue;
                }

                $hw = Homework::create([
                    'school_id' => $schoolId,
                    'section_id' => $section->id,
                    'subject_id' => $attached->subject_id,
                    'title' => 'TEST-Homework '.($idx + 1).' — '.$section->course->name,
                    'description' => 'Demonstration assignment covering this week’s topics.',
                    'due_date' => now()->addDays(rand(3, 14))->toDateString(),
                ]);
                $track('homeworks', [$hw->id]);

                // A few section students submit.
                $sectionStudents = Student::withoutGlobalScopes()
                    ->whereHas('enrollments', fn ($q) => $q->where('section_id', $section->id))
                    ->whereIn('id', $studentIdsList)
                    ->get();
                foreach ($sectionStudents->take(3) as $student) {
                    $sub = HomeworkSubmission::create([
                        'school_id' => $schoolId,
                        'homework_id' => $hw->id,
                        'student_id' => $student->id,
                        'file_path' => 'homework/demo/'.$schoolId.'/'.$hw->id.'-'.$student->id.'.pdf',
                        'grade_obtained' => rand(6, 10) / 2,
                        'teacher_feedback' => 'Well done. Revise question 3 before the test.',
                        'submitted_at' => now()->subDays(rand(0, 2)),
                    ]);
                    $track('homework_submissions', [$sub->id]);
                }
            }
        }
    }

    protected function seedCommunication(int $schoolId, ?int $actorId, array $staffUserIds, callable $track): void
    {
        $announcements = [
            ['Staff meeting — Monday 07:30', 'All teaching staff to assemble in the staff room for the term briefing.', 'important'],
            ['Sports day registration open', 'Parents are reminded to register their children for the inter-house sports day.', 'normal'],
            ['Library extended hours', 'The library will now close at 16:30 on weekdays during exam preparations.', 'normal'],
        ];
        foreach ($announcements as [$title, $content, $priority]) {
            if (Announcement::withoutGlobalScopes()->where('school_id', $schoolId)->where('title', 'LIKE', 'TEST-%'.$title)->exists()) {
                continue;
            }
            $ann = Announcement::create([
                'school_id' => $schoolId,
                'title' => 'TEST-'.$title,
                'content' => $content,
                'published_at' => now()->subDays(rand(1, 9)),
                'status' => 'published',
                'visibility' => ['staff'],
                'priority' => $priority,
                'display_style' => 'card',
            ]);
            $track('communication_announcements', [$ann->id]);
        }

        $allStaffUsers = array_merge($staffUserIds['teaching_staff'], $staffUserIds['non_teaching_staff'], $staffUserIds['administrator']);
        $allStaffUsers = array_values(array_filter($allStaffUsers));

        $tasks = [
            ['Submit term 2 exam marks', now()->addDays(3), 'in_progress'],
            ['Update hostel occupancy list', now()->addDays(7), 'open'],
            ['Draft sports day budget', now()->addDays(5), 'open'],
            ['Backup student records', now()->addDays(1), 'open'],
            ['Review library overdue fines', now()->addDays(4), 'in_progress'],
        ];
        foreach ($tasks as $i => [$title, $due, $status]) {
            $task = UserTask::create([
                'school_id' => $schoolId,
                'created_by_id' => $actorId,
                'assigned_to_id' => $allStaffUsers[$i % max(1, count($allStaffUsers))],
                'title' => 'TEST-Task: '.$title,
                'description' => 'Demonstration task assigned to keep the task board alive.',
                'due_date' => $due->toDateString(),
                'due_time' => '12:00',
                'priority' => $i % 2 === 0 ? 'high' : 'medium',
                'status' => $status,
            ]);
            $track('user_tasks', [$task->id]);
        }

        $polls = [
            ['Which day suits the inter-house sports day?', ['Friday', 'Saturday', 'Sunday', 'Holiday']],
            ['Should the tuck shop stock healthy snacks only?', ['Yes', 'No', 'Mixed options', 'Not sure']],
            ['Preferred end-of-term excursion', ['Matopos', 'Zimbabwe Museum of Human Sciences', 'Chinhoyi Caves', 'Nyanga']],
        ];

        $voters = array_slice($allStaffUsers, 0, 8);
        foreach ($polls as $pi => [$question, $options]) {
            if (Poll::withoutGlobalScopes()->where('school_id', $schoolId)->where('question', 'LIKE', 'TEST-%'.$question)->exists()) {
                continue;
            }
            $poll = Poll::create([
                'school_id' => $schoolId,
                'question' => 'TEST-'.$question,
                'description' => 'Demonstration poll for staff engagement.',
                'type' => 'poll',
                'is_anonymous' => false,
                'target_roles' => ['teaching_staff', 'non_teaching_staff'],
                'expires_at' => now()->addDays(rand(5, 20)),
            ]);
            $track('communication_polls', [$poll->id]);

            $optionIds = [];
            foreach ($options as $opt) {
                $option = PollOption::create([
                    'school_id' => $schoolId,
                    'poll_id' => $poll->id,
                    'option_value' => $opt,
                ]);
                $track('communication_poll_options', [$option->id]);
                $optionIds[] = $option->id;
            }

            foreach ($voters as $userId) {
                $vote = PollVote::create([
                    'school_id' => $schoolId,
                    'poll_id' => $poll->id,
                    'option_id' => $optionIds[array_rand($optionIds)],
                    'user_id' => $userId,
                ]);
                $track('communication_poll_votes', [$vote->id]);
            }
        }
    }

    protected function seedDigitalAssessment(int $schoolId, $year, $term, $sections, array $primarySubjects, array $secondarySubjects, array $subjectObjects, ?int $actorId, callable $track): void
    {
        if (! $actorId) {
            return;
        }

        $questionTemplates = [
            'Which of the following best completes the concept in this topic?',
            'Identify the correct statement below.',
            'Choose the most appropriate answer from the options.',
            'Which statement is TRUE?',
        ];
        $optionSets = [
            ['Option A', 'Option B', 'Option C', 'Option D'],
            ['Statement P', 'Statement Q', 'Statement R', 'Statement S'],
        ];

        foreach ($sections->groupBy('course_id') as $courseId => $courseSections) {
            $course = Course::withoutGlobalScopes()->find($courseId);
            if (! $course) {
                continue;
            }

            $firstSection = $courseSections->first();
            $attached = DB::table('course_subject')
                ->where('school_id', $schoolId)
                ->where('course_id', $courseId)
                ->orderBy('id')
                ->first();
            if (! $attached) {
                continue;
            }

            $subject = Subject::withoutGlobalScopes()->find($attached->subject_id);
            if (! $subject) {
                continue;
            }

            // Ensure the subject has a small question bank.
            $questionIds = [];
            foreach ($questionTemplates as $qi => $text) {
                $qbTitle = 'TEST-Q-'.$subject->code.'-'.($qi + 1);
                $qb = QuestionBank::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('subject_id', $subject->id)
                    ->where('title', $qbTitle)
                    ->first();
                if (! $qb) {
                    $qb = QuestionBank::create([
                        'school_id' => $schoolId,
                        'subject_id' => $subject->id,
                        'created_by_id' => $actorId,
                        'title' => $qbTitle,
                        'description' => 'Auto-generated demonstration question.',
                        'question_type' => 'multiple_choice',
                        'question_text' => $text,
                        'options' => ['A' => 'Option A', 'B' => 'Option B', 'C' => 'Option C', 'D' => 'Option D'],
                        'correct_answer' => ['A'],
                        'marks' => 2.00,
                        'difficulty' => ['foundation', 'intermediate', 'expert'][$qi % 3],
                        'topic' => $subject->name,
                        'status' => 'published',
                    ]);
                    $track('question_bank', [$qb->id]);
                }
                $questionIds[] = $qb->id;
            }

            $assessmentTitle = 'TEST-DA-'.$course->name.' '.$subject->name;
            $assessment = DigitalAssessment::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('title', $assessmentTitle)
                ->where('subject_id', $subject->id)
                ->first();
            if (! $assessment) {
                $assessment = DigitalAssessment::create([
                    'school_id' => $schoolId,
                    'subject_id' => $subject->id,
                    'section_id' => $firstSection->id,
                    'academic_year_id' => $year->id,
                    'term_id' => $term->id,
                    'created_by_id' => $actorId,
                    'title' => $assessmentTitle,
                    'description' => 'Demo online assessment for '.$course->name,
                    'assessment_mode' => 'standard',
                    'assessment_category' => 'formative',
                    'duration_minutes' => 20,
                    'total_marks' => count($questionIds) * 2,
                    'pass_mark' => 50,
                    'max_attempts' => 2,
                    'attempts_allowed' => 2,
                    'randomize_questions' => true,
                    'randomize_options' => true,
                    'show_feedback' => true,
                    'auto_submit' => true,
                    'status' => 'published',
                    'published_at' => now()->subDays(rand(1, 7)),
                    'availability_start_at' => now()->subWeek(),
                    'availability_end_at' => now()->addWeeks(2),
                ]);
                $track('digital_assessments', [$assessment->id]);

                foreach ($questionIds as $order => $qbId) {
                    $daq = DigitalAssessmentQuestion::create([
                        'digital_assessment_id' => $assessment->id,
                        'question_bank_id' => $qbId,
                        'question_order' => $order + 1,
                    ]);
                    $track('digital_assessment_questions', [$daq->id]);
                }
            }

            // Attempts + responses for this section's students.
            $sectionStudents = Student::withoutGlobalScopes()
                ->whereHas('enrollments', fn ($q) => $q->where('section_id', $firstSection->id))
                ->take(5)
                ->get();

            foreach ($sectionStudents as $student) {
                $enrollment = Enrollment::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->first();

                if (DigitalAssessmentAttempt::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('digital_assessment_id', $assessment->id)
                    ->where('student_id', $student->id)
                    ->where('attempt_number', 1)
                    ->exists()) {
                    continue;
                }

                $score = rand(40, 96);
                $attempt = DigitalAssessmentAttempt::create([
                    'school_id' => $schoolId,
                    'digital_assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id ?? null,
                    'attempt_number' => 1,
                    'started_at' => now()->subDays(rand(1, 6))->subMinutes(rand(5, 30)),
                    'submitted_at' => now()->subDays(rand(0, 5)),
                    'duration_seconds' => rand(300, 900),
                    'score' => $score,
                    'percentage' => $score,
                    'final_score' => $score,
                    'marks_obtained' => $score,
                    'max_possible_marks' => 100,
                    'status' => 'submitted',
                ]);
                $track('digital_assessment_attempts', [$attempt->id]);

                foreach ($questionIds as $qbId) {
                    $correct = (bool) random_int(0, 1);
                    $response = DigitalAssessmentResponse::create([
                        'digital_assessment_attempt_id' => $attempt->id,
                        'question_bank_id' => $qbId,
                        'learner_answer' => $correct ? ['A'] : ['C'],
                        'correct_answer' => ['A'],
                        'is_correct' => $correct,
                        'marks_awarded' => $correct ? 2.00 : 0.00,
                        'marks_possible' => 2.00,
                        'time_spent_seconds' => rand(10, 90),
                        'answered_at' => $attempt->submitted_at,
                        'confidence_level' => rand(1, 3),
                    ]);
                    $track('digital_assessment_responses', [$response->id]);
                }
            }
        }

        // Academic assessment type definitions powering the marks UI.
        // Created globally (not per course/section) so they appear once in the
        // "Select Specific Tests / Assessments" list: Test 1 (20%), Test 2 (20%),
        // End of Term Exam (60%).
        $existingTypes = AssessmentType::withoutGlobalScopes()->where('school_id', $schoolId)->count();
        if ($existingTypes === 0) {
            foreach ([
                ['Test 1', 100, 20],
                ['Test 2', 100, 20],
                ['End of Term Exam', 100, 60],
            ] as [$name, $max, $weight]) {
                $type = AssessmentType::create([
                    'school_id' => $schoolId,
                    'term_id' => $term->id,
                    'name' => $name,
                    'max_mark' => $max,
                    'weight_percentage' => $weight,
                    'status' => 'published',
                    'created_by_id' => $actorId,
                ]);
                $track('assessment_types', [$type->id]);
            }
        }
    }

    protected function seedSubjectMarks(int $schoolId, $term, ?int $actorId, callable $track): void
    {
        // Traditional per-subject marks so report cards print real data for at
        // least the first four subjects attached to each course.
        $types = AssessmentType::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->get();

        if ($types->isEmpty()) {
            return;
        }

        $courseSubjects = DB::table('course_subject')
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->get()
            ->groupBy('course_id')
            ->map(fn ($rows) => $rows->pluck('subject_id')->all());

        Enrollment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $term->academic_year_id)
            ->orderBy('id')
            ->chunkById(200, function ($enrollments) use ($schoolId, $types, $courseSubjects, $track) {
                foreach ($enrollments as $enrollment) {
                    $subjectIds = $courseSubjects->get($enrollment->course_id) ?? [];
                    if (empty($subjectIds)) {
                        continue;
                    }

                    foreach ($subjectIds as $subjectId) {
                        foreach ($types as $type) {
                            if (AssessmentMark::withoutGlobalScopes()
                                ->where('school_id', $schoolId)
                                ->where('enrollment_id', $enrollment->id)
                                ->where('subject_id', $subjectId)
                                ->where('assessment_type_id', $type->id)
                                ->exists()) {
                                continue;
                            }

                            $max = (float) $type->max_mark;
                            $mark = $max > 0 ? round($max * (rand(35, 95) / 100), 1) : rand(30, 90);

                            $record = AssessmentMark::create([
                                'school_id' => $schoolId,
                                'enrollment_id' => $enrollment->id,
                                'assessment_type_id' => $type->id,
                                'subject_id' => $subjectId,
                                'marks_obtained' => $mark,
                                'teacher_initials' => 'TR',
                            ]);
                            $track('assessment_marks', [$record->id]);
                        }
                    }
                }
            });
    }

    protected function seedEnterpriseReports(int $schoolId, $term, ?int $actorId, callable $track): void
    {
        $reportTemplateSpecs = [
            ['Student Directory Export', 'students', 'tabular', 'directory'],
            ['Fee Collection Summary', 'finance', 'summary', 'financial'],
            ['Attendance Register', 'attendance', 'tabular', 'operations'],
        ];
        foreach ($reportTemplateSpecs as $n => [$name, $module, $type, $category]) {
            $existsTpl = EnterpriseReportTemplate::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('name', 'LIKE', "TEST-%{$name}")
                ->exists();
            if ($existsTpl) {
                continue;
            }

            $template = EnterpriseReportTemplate::create([
                'school_id' => $schoolId,
                'name' => 'TEST-Demo '.$name,
                'module' => $module,
                'report_type' => $type,
                'report_category' => $category,
                'sharing_scope' => 'school',
                'orientation' => 'portrait',
                'selected_fields' => [],
                'layout_settings' => [],
                'datasets' => [],
                'joins' => [],
                'filters' => [],
                'grouping' => [],
                'calculations' => [],
                'sorting' => [],
                'visualizations' => [],
            ]);
            $track('enterprise_report_templates', [$template->id]);

            for ($g = 1; $g <= 2; $g++) {
                $generated = GeneratedReport::create([
                    'school_id' => $schoolId,
                    'enterprise_report_template_id' => $template->id,
                    'name' => 'TEST-Demo '.$name.' — Run '.$g,
                    'format' => collect(['pdf', 'xlsx', 'csv'])->random(),
                    'file_path' => 'reports/demo/'.$schoolId.'/'.Str::uuid().'.pdf',
                    'status' => 'completed',
                    'record_count' => rand(15, 400),
                    'execution_ms' => rand(120, 2400),
                    'data_checksum' => hash('sha256', $schoolId.$n.$g.microtime()),
                    'data_validated' => true,
                    'validated_at' => now()->subDays(rand(1, 10)),
                    'summary' => 'Demonstration compiled report run.',
                    'filters_used' => json_encode(['term' => $term->name]),
                    'generated_by_id' => $actorId,
                ]);
                $track('generated_reports', [$generated->id]);
            }
        }

        $scheduleExists = ReportSchedule::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('name', 'LIKE', 'TEST-%')
            ->exists();
        if (! $scheduleExists) {
            $firstTemplate = EnterpriseReportTemplate::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('name', 'LIKE', 'TEST-%')
                ->orderBy('id')
                ->first();

            if ($firstTemplate) {
                $schedule = ReportSchedule::create([
                    'school_id' => $schoolId,
                    'enterprise_report_template_id' => $firstTemplate->id,
                    'name' => 'TEST-Weekly Student Directory Mail-out',
                    'frequency' => 'weekly',
                    'distribution_method' => 'email',
                    'output_format' => 'pdf',
                    'generate_on_demand' => false,
                    'recipients' => [$this->schoolContactEmail($schoolId)],
                    'filter_overrides' => [],
                    'is_active' => true,
                    'next_run_at' => now()->addWeek(),
                ]);
                $track('enterprise_report_schedules', [$schedule->id]);
            }
        }
    }

    /**
     * The most recent N school weekdays (as Y-m-d strings).
     */
    protected function recentWeekdays(int $count = 10): array
    {
        $weekdays = [];
        $cursor = now()->copy();
        while (count($weekdays) < $count) {
            if (! $cursor->isWeekend()) {
                $weekdays[] = $cursor->toDateString();
            }
            $cursor->subDay();
        }

        return $weekdays;
    }

    /**
     * The school's recorded contact email (used as demo schedule recipient).
     */
    protected function schoolContactEmail(int $schoolId): string
    {
        return optional(School::find($schoolId))->email_address
            ?? 'admin@demo.schoolcore.test';
    }

    /**
     * Wipe every demonstration record this seeder previously created for the
     * school (per the stored seed manifest), plus legacy TEST-tagged rows.
     *
     * @return int number of student rows removed
     */
    public function wipe(int $schoolId): int
    {
        @set_time_limit(600);

        $manifest = $this->loadManifest($schoolId);

        DB::transaction(function () use ($manifest, $schoolId): void {
            // Older demo datasets seeded hostel rooms/beds/allocations before
            // the manifest existed, so the manifest may only track the rooms.
            $roomIds = array_map('intval', $manifest['hostel_rooms'] ?? []);
            if ($roomIds !== []) {
                $bedIds = DB::table('hostel_beds')->whereIn('room_id', $roomIds)->pluck('id');
                if ($bedIds->isNotEmpty()) {
                    DB::table('hostel_allocations')->whereIn('bed_id', $bedIds)->delete();
                    DB::table('hostel_beds')->whereIn('id', $bedIds)->delete();
                }
            }

            foreach (self::MANIFEST_DELETE_ORDER as $table) {
                $ids = $manifest[$table] ?? [];
                if (empty($ids)) {
                    continue;
                }

                foreach (array_chunk($ids, 500) as $chunk) {
                    $query = DB::table($table);

                    if (! in_array($table, self::MANIFEST_NO_SCHOOL_COLUMN, true)) {
                        $query->where('school_id', $schoolId);
                    }

                    $query->whereIn('id', $chunk)->delete();
                }
            }

            // Legacy datasets (pre-manifest) and any manifest-orphaned rows:
            // remove every TEST-tagged demo record by its marker column.
            $demoInvoices = Invoice::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('invoice_number', 'LIKE', 'TEST-INV-%')
                ->pluck('id');
            if ($demoInvoices->isNotEmpty()) {
                DB::table('invoice_items')->whereIn('invoice_id', $demoInvoices)->delete();
                Invoice::withoutGlobalScopes()->whereIn('id', $demoInvoices)->delete();
            }

            Expense::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('reference_number', 'LIKE', 'TEST-EXP-%')->delete();

            FixedAsset::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('asset_number', 'LIKE', 'TEST-AST-%')->delete();
            InventoryItem::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('sku', 'LIKE', 'TEST-SKU-%')->delete();

            LibraryBook::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('isbn', 'LIKE', 'TEST-ISBN-%')->delete();
            LibraryBookCopy::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('barcode', 'LIKE', 'TEST-BC-%')
                ->delete();
            KnowledgeAssetCopy::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('barcode', 'LIKE', 'TEST-KB-%')
                ->delete();
            KnowledgeAsset::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('isbn', 'LIKE', 'TEST-KA-%')
                ->delete();

            Homework::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('title', 'LIKE', 'TEST-%')->delete();

            QuestionBank::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('title', 'LIKE', 'TEST-Q-%')->delete();
            DigitalAssessment::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('title', 'LIKE', 'TEST-DA-%')->delete();
            ProcurementRequest::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('request_number', 'LIKE', 'TEST-PREQ-%')->delete();
            ProcurementOrder::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('order_number', 'LIKE', 'TEST-PO-%')->delete();
            Application::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('application_number', 'LIKE', 'TEST-APP-%')->delete();
            Announcement::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('title', 'LIKE', 'TEST-%')->delete();
            UserTask::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('title', 'LIKE', 'TEST-%')->delete();

            // Demo staff: Employee::creating() rewrites employee_number, so
            // they carry the reserved demo email domain instead.
            DB::table('employees')
                ->where('school_id', $schoolId)
                ->where('email', 'LIKE', '%@demo.schoolcore.test')
                ->delete();

            $legacyStudents = Student::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('student_id_number', 'LIKE', 'TEST-STU-%')
                ->get();

            if ($legacyStudents->isNotEmpty()) {
                $legacyIds = $legacyStudents->pluck('id');
                $legacyEnrollmentIds = Enrollment::withoutGlobalScopes()->whereIn('student_id', $legacyIds)->pluck('id');

                AcademicReport::withoutGlobalScopes()->whereIn('student_id', $legacyIds)->delete();
                if ($legacyEnrollmentIds->isNotEmpty()) {
                    AssessmentMarksLedger::withoutGlobalScopes()->whereIn('enrollment_id', $legacyEnrollmentIds)->delete();
                }
                Enrollment::withoutGlobalScopes()->whereIn('student_id', $legacyIds)->delete();
                Student::withoutGlobalScopes()->whereIn('id', $legacyIds)->forceDelete();
            }

            // Demo student portal accounts (created by ensureDemoStudentAccounts).
            // Deleted only after every TEST-STU student above is gone so the
            // students.user_id FK is never violated.
            DB::table('users')
                ->where('school_id', $schoolId)
                ->where('requested_role', 'student')
                ->where('username', 'LIKE', 'TEST-STU-%')
                ->delete();
        });

        // Clear the manifest — everything it described is gone.
        self::forgetManifestRow($schoolId);

        return count($manifest['students'] ?? []);
    }

    /**
     * Whether the school currently has demonstration students.
     */
    public function hasDemoData(int $schoolId): bool
    {
        return Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->exists();
    }

    /**
     * Shared demo credential for every seeded student portal account.
     */
    public string $demoStudentPassword = 'password';

    /**
     * Create one student-portal login per seeded student that does not have
     * a linked user yet. Idempotent: existing demo accounts are reused and
     * students are never detached from a previously-linked account.
     *
     * @return array<int, array{name: string, username: string, password: string}>
     */
    public function ensureDemoStudentAccounts(int $schoolId): array
    {
        $students = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->whereNull('user_id')
            ->get();

        $accounts = [];

        foreach ($students as $student) {
            $user = $this->findOrCreateDemoStudentUser($schoolId, $student);

            if (! $user) {
                continue;
            }

            if ($student->user_id !== $user->id) {
                $student->forceFill(['user_id' => $user->id])->save();
            }

            $accounts[] = [
                'name' => $student->full_name,
                'username' => $student->student_id_number,
                'password' => $this->demoStudentPassword,
            ];
        }

        return $accounts;
    }

    protected function findOrCreateDemoStudentUser(int $schoolId, Student $student): ?User
    {
        $school = School::find($schoolId);
        $slug = $school ? strtolower(preg_replace('/[^a-z0-9]+/', '', (string) $school->subdomain)) : 'school';
        $email = 'student.'.$student->id.'@'.$slug.'.demo';

        $user = User::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->first();

        if ($user) {
            return $user;
        }

        $role = UserRegistrationService::ensureRoleForCategory($schoolId, 'student');

        return User::withoutGlobalScopes()->create([
            'school_id' => $schoolId,
            'name' => $student->full_name,
            'username' => $student->student_id_number,
            'email' => $email,
            'phone' => $student->phone,
            // Demo-only credentials: a low bcrypt cost keeps the ~90 student
            // logins cheap to create during seeding while reusing the fresh
            // Hash facade. The 'hashed' cast stores this value as-is (it is
            // already a valid bcrypt hash, so no re-hash occurs).
            'password' => Hash::make($this->demoStudentPassword, ['rounds' => 6]),
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => 'student',
            'custom_role_id' => $role->id,
        ]);
    }

    public function enforceInstitutionTypeScope(int $schoolId): void
    {
        $school = School::find($schoolId);
        if (! $school) {
            return;
        }

        $schoolType = strtolower((string) ($school->institution_type ?? 'secondary'));
        $isPrimary = in_array($schoolType, ['primary'], true);
        $isSecondary = in_array($schoolType, ['secondary'], true);

        if ($isPrimary) {
            $validPrimaryNames = ['ECD A', 'ECD B', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'];
            $invalidCourseIds = Course::where('school_id', $schoolId)
                ->whereNotIn('name', $validPrimaryNames)
                ->pluck('id');

            if ($invalidCourseIds->isNotEmpty()) {
                Section::whereIn('course_id', $invalidCourseIds)->delete();
                Course::whereIn('id', $invalidCourseIds)->delete();
            }

            $validPrimarySubjects = ['Mathematics', 'English Language', 'Shona Language', 'Science & Technology', 'Social Studies', 'Physical Education'];
            $invalidSubjectIds = Subject::where('school_id', $schoolId)
                ->whereNotIn('name', $validPrimarySubjects)
                ->pluck('id');

            if ($invalidSubjectIds->isNotEmpty()) {
                Subject::whereIn('id', $invalidSubjectIds)->delete();
            }
        } elseif ($isSecondary) {
            $invalidCourseIds = Course::where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->where('name', 'LIKE', '%ECD%')
                        ->orWhere('name', 'LIKE', '%Grade%');
                })
                ->pluck('id');

            if ($invalidCourseIds->isNotEmpty()) {
                Section::whereIn('course_id', $invalidCourseIds)->delete();
                Course::whereIn('id', $invalidCourseIds)->delete();
            }
        }
    }

    protected function manifestKey(): string
    {
        return 'seed_manifest';
    }

    protected function saveManifest(int $schoolId, array $manifest): void
    {
        // MERGE with any existing manifest so wiping always removes every row
        // this seeder has EVER created for the school, not just the latest run.
        $merged = $this->loadManifest($schoolId);

        foreach ($manifest as $table => $ids) {
            $merged[$table] = array_values(array_unique(array_merge(
                $merged[$table] ?? [],
                array_map('intval', $ids)
            )));
        }

        SystemSetting::set('demo', $this->manifestKey(), $merged, $schoolId);
    }

    protected function loadManifest(int $schoolId): array
    {
        $raw = SystemSetting::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('group', 'demo')
            ->where('key', $this->manifestKey())
            ->value('value');

        if (blank($raw)) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected static function forgetManifestRow(int $schoolId): void
    {
        SystemSetting::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('group', 'demo')
            ->where('key', 'seed_manifest')
            ->delete();
    }
}
