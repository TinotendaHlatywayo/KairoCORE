<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Assessment;
use Modules\Academics\Models\AssessmentMarksLedger;
use Modules\Academics\Models\AssessmentPlan;
use Modules\Academics\Models\AssessmentPlanComponent;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Clinic\Models\ClinicVisit;
use Modules\Clinic\Models\StudentMedicalRecord;
use Modules\Attendance\Models\StudentAttendance;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\Finance\Models\FeeCategory;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Supplier;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;
use Modules\Hostels\Models\HostelBuilding;
use Modules\Hostels\Models\HostelFloor;
use Modules\Hostels\Models\HostelRoom;
use Modules\Hostels\Models\HostelWing;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryCategory;
use Modules\Library\Models\LibraryFormat;
use Modules\Lms\Models\Homework;
use Modules\Admin\Models\SystemSetting;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

/**
 * Seeds (and wipes) the playground demonstration dataset for a school tenant.
 *
 * This is the single implementation behind the `schoolcore:dummy` artisan
 * command, the dashboard "Seed/Wipe Demo Data" widget AND the registration
 * "Pre-load Demonstration Data" option, so the demo data produced everywhere
 * is identical.
 *
 * Comprehensive coverage: academics, grading, students & reports, finance,
 * HR & payroll, inventory & assets, library, clinic, hostels, attendance
 * and LMS homework — enough interlinked data to exercise every module
 * before real data arrives.
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
        'clinic_visits',
        'student_medical_records',
        'homeworks',
        'student_attendances',
        'timetable_lessons',
        'time_slots',
        'classrooms',
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
        'grading_points',
        'grading_scales',
        'assessment_marks_ledger',
        'enrollments',
        'academic_reports',
        'students',
        'sections',
        'subjects',
        'courses',
        'terms',
        'academic_years',
    ];

    public function seed(int $schoolId, ?callable $log = null): array
    {
        $log ??= fn () => null;

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
            foreach ((array) ($ids instanceof \Illuminate\Support\Collection ? $ids->all() : $ids) as $id) {
                $manifest[$table][] = (int) $id;
            }
        };

        $created = function ($model) use ($track, &$manifest): mixed {
            if ($model->wasRecentlyCreated) {
                $table = $model->getTable();
                $manifest[$table][] = (int) $model->id;
            }

            return $model;
        };

        // An acting user for "recorded_by"-style columns (admin if present).
        $actorId = optional(\App\Models\User::where('school_id', $schoolId)->orderBy('id')->first())->id;

        // ════════════════════════════════════════════════════════════════
        // 1. ACADEMICS: year, terms, courses, streams, subjects, grading
        // ════════════════════════════════════════════════════════════════
        $year = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (! $year) {
            $year = $created(AcademicYear::create([
                'school_id' => $schoolId,
                'name' => now()->format('Y').' Academic Year',
                'is_active' => true,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ]));
        }

        $term = Term::where('school_id', $schoolId)->where('academic_year_id', $year->id)->orderBy('id')->first();
        if (! $term) {
            $term = $created(Term::create([
                'school_id' => $schoolId,
                'name' => 'Term 1',
                'academic_year_id' => $year->id,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->startOfYear()->addMonths(3)->toDateString(),
            ]));
        }
        $term2Id = optional(Term::where('school_id', $schoolId)->where('academic_year_id', $year->id)->skip(1)->first())->id;

        $courseSpecs = [
            ['ECD A', 'ecda', 'ECD-A', 'ecd'],
            ['Grade 1', 'primary', 'G1', 'grade_1_7'],
            ['Grade 2', 'primary', 'G2', 'grade_1_7'],
            ['Form 1', 'secondary', 'F1', 'form_1_4'],
            ['Form 2', 'secondary', 'F2', 'form_1_4'],
        ];
        $courseIds = [];
        foreach ($courseSpecs as [$name, $level, $code, $scope]) {
            $course = Course::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['level' => $level, 'code' => $code]
            );
            $courseIds[$scope] = $course->id;
            $created($course);
        }

        // Streams: two per course for the demo levels.
        $sectionQuery = Section::where('school_id', $schoolId);
        $existingSections = $sectionQuery->count();

        if ($existingSections === 0) {
            foreach (array_unique(array_intersect_key($courseIds, array_flip(['grade_1_7', 'ecd', 'form_1_4']))) as $courseId) {
                foreach (['North', 'South'] as $stream) {
                    $created(Section::create([
                        'school_id' => $schoolId,
                        'course_id' => $courseId,
                        'name' => $stream,
                        'capacity' => 40,
                    ]));
                }
            }
        }

        // Eager-load course relation (Model::shouldBeStrict forbids lazy loading).
        $sections = Section::where('school_id', $schoolId)->with('course')->get();

        $subjectSpecs = [
            ['Mathematics', 'M101', 'theory'],
            ['English Language', 'ENG101', 'theory'],
            ['Combined Science', 'SCI101', 'theory'],
            ['Physical Education', 'PE101', 'practical'],
            ['Computer Science', 'CS101', 'practical'],
        ];
        $subjectIds = [];
        foreach ($subjectSpecs as [$name, $code, $type]) {
            $subject = Subject::firstOrCreate(
                ['school_id' => $schoolId, 'code' => $code],
                ['name' => $name, 'type' => $type, 'credit_weight' => 1.00, 'is_elective' => false]
            );
            $subjectIds[] = $subject->id;
            $created($subject);
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
        // 2. STUDENTS + ENROLLMENTS + ASSESSMENTS + MARKS + REPORTS
        // ════════════════════════════════════════════════════════════════
        $studentOffset = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->count();

        $firstNamesMale = ['Tatenda', 'Tinashe', 'Tariro', 'Tanaka', 'Kudakwashe', 'Farai', 'Simbarashe', 'Rufaro', 'Munashe', 'Tendai'];
        $firstNamesFemale = ['Ruvimbo', 'Chipo', 'Nyasha', 'Rudo', 'Tsitsi', 'Fadzai', 'Sekai', 'Nokutenda', 'Tadiwanashe', 'Rutendo'];
        $surnames = ['Moyo', 'Sibanda', 'Ndlovu', 'Dube', 'Mutasa', 'Gumbo', 'Zhou', 'Shumba', 'Mpofu', 'Maphosa'];
        $houses = ['Nyanga', 'Chiadzwa', 'Chimanimani', 'Vumba'];
        $bloodGroups = ['O+', 'O-', 'A+', 'B+', 'AB+'];
        $medicalNotes = ['None', 'Mild pollen allergy', 'Requires asthma inhaler near sports field', 'None', 'None'];

        $studentCount = $studentOffset;
        $reportCount = 0;

        foreach ($sections as $section) {
            $course = $section->course;

            if (! $course) {
                continue;
            }

            $log("Seeding 10 students into stream: {$course->name} {$section->name}...");

            $plan = AssessmentPlan::firstOrCreate([
                'school_id' => $schoolId,
                'term_id' => $term->id,
                'course_id' => $course->id,
                'subject_id' => $subjectIds[0],
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
                'name' => 'Fraction Quiz',
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
                str_contains(strtolower($course->name), 'ecd') => 5,
                preg_match('/Grade\s*(\d)/i', (string) $course->name, $m) => 5 + intval($m[1]),
                preg_match('/Form\s*(\d)/i', (string) $course->name, $m) => 12 + intval($m[1]),
                default => 10,
            };

            for ($k = 0; $k < 10; $k++) {
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
                    'status' => 'active',
                    'card_status' => 'active',
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

                AcademicReport::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'section_id' => $section->id,
                    'term_id' => $term->id,
                    'overall_score' => rand(55, 92) / 10,
                    'status' => 'approved',
                    'teacher_comment' => 'A focused and highly diligent student who shows steady, remarkable progress.',
                    'headmaster_comment' => 'Impressive score performance this term. Maintain the clean and excellent focus.',
                    'integrity_hash' => hash_hmac('sha256', $stuIdNumber, config('app.key')),
                ]);
                $track('academic_reports', [DB::getPdo()->lastInsertId()]);
                $reportCount++;
            }
        }

        $studentIdsList = $manifest['students'] ?? [];
        $enrollmentIds = $manifest['enrollments'] ?? [];

        // ════════════════════════════════════════════════════════════════
        // 3. FINANCE: fee structures, invoices & items, expenses, suppliers
        // ════════════════════════════════════════════════════════════════
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

        foreach ($studentsForInvoices as $i => $student) {
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

        $expenseCategory = $created(ExpenseCategory::firstOrCreate(
            ['school_id' => $schoolId, 'name' => 'Operational Expenses'],
            ['description' => 'Day-to-day running costs']
        ));
        $expenseType = $created(ExpenseType::firstOrCreate(
            ['school_id' => $schoolId, 'expense_category_id' => $expenseCategory->id, 'name' => 'Stationery & Printing']
        ));

        for ($e = 1; $e <= 6; $e++) {
            $expense = Expense::create([
                'school_id' => $schoolId,
                'expense_type_id' => $expenseType->id,
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'amount' => rand(45, 850),
                'expense_date' => now()->subDays(rand(1, 60))->toDateString(),
                'reference_number' => 'TEST-EXP-'.$schoolId.'-'.str_pad((string) $e, 4, '0', STR_PAD_LEFT),
                'notes' => 'Demonstration expense entry.',
                'status' => collect(['pending', 'approved', 'paid'])->random(),
                'user_id' => $actorId,
            ]);
            $track('expenses', [$expense->id]);
        }

        // ════════════════════════════════════════════════════════════════
        // 4. HR & PAYROLL: salary grades + staff
        // ════════════════════════════════════════════════════════════════
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
            $gradeIds[] = $grade->id;
            $created($grade);
        }

        $staffSpecs = [
            ['Grace', 'Mhaka', 'female', 'Headmistress', 'Administration', 'non_teaching_staff', 0],
            ['Petros', 'Ngwenya', 'male', 'Senior Teacher — Mathematics', 'Academic', 'teaching_staff', 1],
            ['Rudo', 'Chieza', 'female', 'Teacher — English', 'Academic', 'teaching_staff', 2],
            ['Farai', 'Muchena', 'male', 'Teacher — Sciences', 'Academic', 'teaching_staff', 2],
            ['Nyarai', 'Dzimiri', 'female', 'Teacher — Humanities', 'Academic', 'teaching_staff', 2],
            ['Takura', 'Mapfumo', 'male', 'Sports Coach', 'Academic', 'teaching_staff', 2],
            ['Sarah', 'Bvute', 'female', 'School Bursar', 'Finance', 'non_teaching_staff', 1],
            ['James', 'Zvobgo', 'male', 'School Nurse', 'Health', 'non_teaching_staff', 3],
        ];
        foreach ($staffSpecs as $n => [$first, $last, $gender, $designation, $department, $role, $gradeIdx]) {
            // NOTE: Employee::creating() regenerates employee_number as
            // "EMP-YYYY-####", so demo staff are identified by their reserved
            // demo email domain instead.
            $demoEmail = strtolower($first.'.'.$last.'@demo.schoolcore.test');

            $existingEmp = Employee::withoutGlobalScopes()
                ->where('school_id', $schoolId)->where('email', $demoEmail)->first();
            if ($existingEmp) {
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
                'employment_type' => 'full_time',
                'date_joined' => now()->subYears(rand(1, 10))->subMonths(rand(0, 11)),
                'current_grade_id' => $gradeIds[$gradeIdx],
            ]);
            $track('employees', [$employee->id]);
        }

        // ════════════════════════════════════════════════════════════════
        // 5. INVENTORY & FIXED ASSETS
        // ════════════════════════════════════════════════════════════════
        $invCats = [];
        foreach ([['Stationery', 'Paper, pens and office supplies'], ['Cleaning Materials', 'Janitorial consumables'], ['ICT Equipment', 'Computers and peripherals']] as [$name, $desc]) {
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
        ];
        $assetEligibleItems = [];
        foreach ($itemSpecs as $n => [$catIdx, $name, $type, $uom, $reorder, $qty, $cost]) {
            $item = InventoryItem::firstOrCreate(
                ['school_id' => $schoolId, 'sku' => 'TEST-SKU-'.$schoolId.'-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'category_id' => $invCats[$catIdx]->id,
                    'name' => $name,
                    'item_type' => $type,
                    'unit_of_measure' => $uom,
                    'reorder_level' => $reorder,
                    'current_quantity' => $qty,
                    'average_unit_cost' => $cost,
                    'is_saleable' => false,
                ]
            );
            if ($type === 'fixed_asset') {
                $assetEligibleItems[] = $item;
            }
            $created($item);
        }

        foreach ($assetEligibleItems as $n => $item) {
            $exists = FixedAsset::withoutGlobalScopes()->where('school_id', $schoolId)
                ->where('inventory_item_id', $item->id)->exists();
            if ($exists) {
                continue;
            }

            $asset = FixedAsset::create([
                'school_id' => $schoolId,
                'inventory_item_id' => $item->id,
                'asset_number' => 'TEST-AST-'.$schoolId.'-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                'serial_number' => 'SN-DEMO-'.strtoupper(\Illuminate\Support\Str::random(8)),
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

        // ════════════════════════════════════════════════════════════════
        // 6. LIBRARY
        // ════════════════════════════════════════════════════════════════
        $libCatFiction = $created(LibraryCategory::firstOrCreate(['school_id' => $schoolId, 'name' => 'Fiction']));
        $libCatReference = $created(LibraryCategory::firstOrCreate(['school_id' => $schoolId, 'name' => 'Reference']));
        $fmtPrint = $created(LibraryFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'Print Book'], ['media_type' => 'physical']));
        $fmtEbook = $created(LibraryFormat::firstOrCreate(['school_id' => $schoolId, 'name' => 'E-Book'], ['media_type' => 'digital']));

        $bookSpecs = [
            [$libCatFiction->id, $fmtPrint->id, 'The House of Hunger', 'Dambudzo Marechera', 1978, 'Fiction'],
            [$libCatFiction->id, $fmtPrint->id, 'Nervous Conditions', 'Tsitsi Dangarembga', 1988, 'Fiction'],
            [$libCatFiction->id, $fmtEbook->id, 'An Elegy for Easterly', 'Petina Gappah', 2009, 'Fiction'],
            [$libCatReference->id, $fmtPrint->id, 'O-Level Mathematics Revision', 'J. Sadler', 2015, 'Mathematics'],
            [$libCatReference->id, $fmtPrint->id, 'Atlas of Southern Africa', 'Maskew Miller', 2012, 'Geography'],
            [$libCatReference->id, $fmtEbook->id, 'Introduction to Programming', 'FreeTech Press', 2020, 'Computer Science'],
        ];
        foreach ($bookSpecs as $n => [$categoryId, $formatId, $title, $author, $pubYear, $subjectName]) {
            $isbn = 'TEST-ISBN-'.$schoolId.'-'.str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);
            $existsBook = LibraryBook::withoutGlobalScopes()->where('school_id', $schoolId)->where('isbn', $isbn)->exists();
            if ($existsBook) {
                continue;
            }

            $book = LibraryBook::create([
                'school_id' => $schoolId,
                'library_category_id' => $categoryId,
                'library_format_id' => $formatId,
                'title' => $title,
                'publisher' => 'Demo Press',
                'publication_year' => $pubYear,
                'isbn' => $isbn,
                'language' => 'English',
                'subject' => $subjectName,
                'media_type' => $formatId === $fmtEbook->id ? 'digital' : 'physical',
                'description' => 'Demonstration library title for testing circulation.',
            ]);
            $track('library_books', [$book->id]);
        }

        // ════════════════════════════════════════════════════════════════
        // 7. CLINIC: medical records + visits
        // ════════════════════════════════════════════════════════════════
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

        // ════════════════════════════════════════════════════════════════
        // 8. HOSTELS: building → floor → wing → rooms → beds → allocations
        // ════════════════════════════════════════════════════════════════
        $boarders = Student::withoutGlobalScopes()->whereIn('id', $studentIdsList)
            ->where('boarding_status', 'boarder')->get();

        if ($boarders->isNotEmpty()) {
            $hostel = $created(Hostel::firstOrCreate(
                ['school_id' => $schoolId, 'name' => 'Main Boys Wing'],
                ['type' => 'boys', 'capacity' => 32, 'status' => 'operational', 'description' => 'Demonstration boarding house.']
            ));

            $building = $created(HostelBuilding::firstOrCreate(
                ['school_id' => $schoolId, 'hostel_id' => $hostel->id, 'name' => 'Block A'],
                ['description' => 'Ground floor block.']
            ));
            $floor = $created(HostelFloor::firstOrCreate(
                ['school_id' => $schoolId, 'building_id' => $building->id, 'floor_number' => 1],
                ['floor_name' => 'Ground Floor']
            ));
            $wing = $created(HostelWing::firstOrCreate(
                ['school_id' => $schoolId, 'floor_id' => $floor->id, 'name' => 'East Wing']
            ));

            $freeBeds = collect();
            foreach ([1, 2] as $roomNo) {
                $room = $created(HostelRoom::firstOrCreate(
                    ['school_id' => $schoolId, 'hostel_id' => $hostel->id, 'room_number' => 'A'.$roomNo],
                    [
                        'wing_id' => $wing->id,
                        'floor_id' => $floor->id,
                        'name' => 'Room A'.$roomNo,
                        'room_type' => 'dormitory',
                        'condition' => 'good',
                        'status' => 'available',
                        'capacity' => 4,
                    ]
                ));

                foreach ([1, 2] as $bedNo) {
                    $bedNumber = 'A'.$roomNo.'-B'.$bedNo;
                    $bed = HostelBed::firstOrCreate(
                        ['school_id' => $schoolId, 'room_id' => $room->id, 'bed_number' => $bedNumber],
                        ['condition' => 'good', 'status' => 'vacant', 'cleaning_status' => 'clean']
                    );
                    if ($bed->wasRecentlyCreated) {
                        $track('hostel_beds', [$bed->id]);
                    } elseif ($bed->status === 'vacant') {
                        // reusable bed from a previous seed whose allocation was wiped
                    }
                    $freeBeds->push($bed);
                }
            }

            foreach ($boarders->zip($freeBeds) as [$student, $bed]) {
                if (! $student || ! $bed) {
                    break;
                }

                $alreadyAllocated = HostelAllocation::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->whereIn('status', ['active'])
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

        // ════════════════════════════════════════════════════════════════
        // 9. TIMETABLE + ATTENDANCE
        // student_attendances.timetable_lesson_id is required, so we build a
        // small demo timetable first (classroom, periods, lessons) and mark
        // attendance against those lessons.
        // ════════════════════════════════════════════════════════════════
        if ($actorId) {
            $classroom = $created(\Modules\Academics\Models\Classroom::firstOrCreate(
                ['school_id' => $schoolId, 'name' => 'Demo Room 1'],
                ['capacity' => 40, 'location' => 'Main Block']
            ));

            $periods = [
                ['Period 1', '08:00', '08:45'],
                ['Period 2', '08:50', '09:35'],
                ['Period 3', '10:00', '10:45'],
                ['Period 4', '10:50', '11:35'],
                ['Period 5', '12:20', '13:05'],
            ];
            $slotIds = [];
            foreach ($periods as [$slotName, $start, $end]) {
                $slot = \Modules\Timetables\Models\TimeSlot::firstOrCreate(
                    ['school_id' => $schoolId, 'name' => $slotName],
                    ['start_time' => $start, 'end_time' => $end, 'is_break' => false]
                );
                $slotIds[] = $slot->id;
                $created($slot);
            }

            // One globally-unique (day, slot) pair per lesson keeps every
            // unique-conflict index happy with a single demo teacher.
            $combos = [];
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
                foreach ($slotIds as $slotId) {
                    $combos[] = [$day, $slotId];
                }
            }
            $comboIndex = 0;

            $sectionLessonIds = [];

            foreach ($sections as $section) {
                if (! $section->course || ! isset($subjectIds[0])) {
                    continue;
                }

                foreach (array_slice($subjectIds, 0, 2) as $subjectId) {
                    if (! isset($combos[$comboIndex])) {
                        break 2;
                    }
                    [$day, $slotId] = $combos[$comboIndex++];

                    $lesson = \Modules\Timetables\Models\TimetableLesson::firstOrCreate([
                        'school_id' => $schoolId,
                        'academic_year_id' => $year->id,
                        'term_id' => $term->id,
                        'time_slot_id' => $slotId,
                        'day_of_week' => $day,
                        'section_id' => $section->id,
                    ], [
                        'course_id' => $section->course->id,
                        'subject_id' => $subjectId,
                        'teacher_id' => $actorId,
                        'classroom_id' => $classroom->id,
                    ]);
                    $created($lesson);

                    $sectionLessonIds[$section->id][] = $lesson->id;
                }
            }

            // Mark attendance on each section's first lesson.
            $weekdays = collect();
            $cursor = now()->copy();
            while ($weekdays->count() < 10) {
                if (! $cursor->isWeekend()) {
                    $weekdays->push($cursor->toDateString());
                }
                $cursor->subDay();
            }

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

        // ════════════════════════════════════════════════════════════════
        // 10. LMS HOMEWORK
        // ════════════════════════════════════════════════════════════════
        foreach ($sections as $section) {
            if (! $section->course) {
                continue;
            }
            foreach (array_slice($subjectIds, 0, 2) as $idx => $subjectId) {
                $existsHw = Homework::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('section_id', $section->id)
                    ->where('subject_id', $subjectId)
                    ->where('title', 'LIKE', 'TEST-%')
                    ->exists();
                if ($existsHw) {
                    continue;
                }

                $hw = Homework::create([
                    'school_id' => $schoolId,
                    'section_id' => $section->id,
                    'subject_id' => $subjectId,
                    'title' => 'TEST-Homework '.($idx + 1).' — '.$section->course->name.' '.$section->name,
                    'description' => 'Demonstration assignment covering this week’s topics.',
                    'due_date' => now()->addDays(rand(3, 14))->toDateString(),
                ]);
                $track('homeworks', [$hw->id]);
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 11. ENTERPRISE REPORTING: templates, compiled reports, schedules
        // ════════════════════════════════════════════════════════════════
        $reportTemplateSpecs = [
            ['Student Directory Export', 'students', 'tabular', 'directory'],
            ['Fee Collection Summary', 'finance', 'summary', 'financial'],
            ['Attendance Register', 'attendance', 'tabular', 'operations'],
        ];
        foreach ($reportTemplateSpecs as $n => [$name, $module, $type, $category]) {
            $existsTpl = \Modules\Reports\Models\EnterpriseReportTemplate::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('name', 'LIKE', "TEST-%{$name}")
                ->exists();
            if ($existsTpl) {
                continue;
            }

            $template = \Modules\Reports\Models\EnterpriseReportTemplate::create([
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

            // A couple of completed runs per template so the archive is alive.
            for ($g = 1; $g <= 2; $g++) {
                $generated = \Modules\Reports\Models\GeneratedReport::create([
                    'school_id' => $schoolId,
                    'enterprise_report_template_id' => $template->id,
                    'name' => 'TEST-Demo '.$name.' — Run '.$g,
                    'format' => collect(['pdf', 'xlsx', 'csv'])->random(),
                    'file_path' => 'reports/demo/'.$schoolId.'/'.\Illuminate\Support\Str::uuid().'.pdf',
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

        $scheduleExists = \Modules\Reports\Models\ReportSchedule::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('name', 'LIKE', 'TEST-%')
            ->exists();
        if (! $scheduleExists) {
            $firstTemplate = \Modules\Reports\Models\EnterpriseReportTemplate::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('name', 'LIKE', 'TEST-%')
                ->orderBy('id')
                ->first();

            if ($firstTemplate) {
                $schedule = \Modules\Reports\Models\ReportSchedule::create([
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
     * The school's recorded contact email (used as demo schedule recipient).
     */
    protected function schoolContactEmail(int $schoolId): string
    {
        return optional(\App\Models\School::find($schoolId))->email_address
            ?? 'admin@demo.schoolcore.test';
    }

    /**
     * Wipe every demonstration record this seeder previously created for the
     * school (per the stored seed manifest), plus legacy TEST-STU students.
     *
     * @return int number of student rows removed
     */
    public function wipe(int $schoolId): int
    {
        $manifest = $this->loadManifest($schoolId);

        DB::transaction(function () use ($manifest, $schoolId): void {
            foreach (self::MANIFEST_DELETE_ORDER as $table) {
                $ids = $manifest[$table] ?? [];
                if (empty($ids)) {
                    continue;
                }

                foreach (array_chunk($ids, 500) as $chunk) {
                    // invoice_items carries no school_id column — the manifest
                    // ids are already school-scoped by construction.
                    $query = DB::table($table);

                    if ($table !== 'invoice_items') {
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

            Homework::withoutGlobalScopes()->where('school_id', $schoolId)
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
