<?php

namespace App\Services;

use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Assessment;
use Modules\Academics\Models\AssessmentMarksLedger;
use Modules\Academics\Models\AssessmentPlan;
use Modules\Academics\Models\AssessmentPlanComponent;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

/**
 * Seeds (and wipes) the playground demonstration dataset for a school tenant.
 *
 * This is the single implementation behind the `schoolcore:dummy` artisan
 * command AND the registration "Pre-load Demonstration Data" option, so the
 * demo data produced during registration is identical to what the main system
 * dashboard seeds.
 */
class DummyDataSeeder
{
    /**
     * Seed the demonstration dataset for the given school.
     *
     * @param  callable(string): void|null  $log  optional logger callback
     */
    public function seed(int $schoolId, ?callable $log = null): array
    {
        $log ??= fn () => null;

        // 1. Fetch or create active academic year and term
        $year = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (! $year) {
            $year = AcademicYear::create([
                'school_id' => $schoolId,
                'name' => '2026 Academic Year',
                'is_active' => true,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);
        }

        $term = Term::where('school_id', $schoolId)->where('academic_year_id', $year->id)->first();
        if (! $term) {
            $term = Term::create([
                'school_id' => $schoolId,
                'name' => 'Term 1',
                'academic_year_id' => $year->id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-04-30',
            ]);
        }

        // 2. Fetch all sections currently existing in the database for this school
        $sections = Section::where('school_id', $schoolId)->get();

        if ($sections->isEmpty()) {
            $log('No sections found in system. Initializing default Grade 1 Class Streams...');
            $course = Course::firstOrCreate(
                ['school_id' => $schoolId, 'name' => 'Grade 1'],
                ['level' => 'primary']
            );

            $sections = collect([
                Section::firstOrCreate(['school_id' => $schoolId, 'name' => 'North', 'course_id' => $course->id]),
                Section::firstOrCreate(['school_id' => $schoolId, 'name' => 'South', 'course_id' => $course->id]),
            ]);
        }

        // Fetch or create a default Subject to link grades to
        $subject = Subject::where('school_id', $schoolId)->first();
        if (! $subject) {
            $subject = Subject::create([
                'school_id' => $schoolId,
                'code' => 'M101',
                'name' => 'Mathematics',
            ]);
        }

        $studentCount = 0;
        $reportCount = 0;

        $firstNamesMale = ['Tatenda', 'Tinashe', 'Tariro', 'Tanaka', 'Kudakwashe', 'Farai', 'Simbarashe', 'Rufaro', 'Munashe', 'Tendai'];
        $firstNamesFemale = ['Ruvimbo', 'Chipo', 'Nyasha', 'Rudo', 'Tsitsi', 'Fadzai', 'Sekai', 'Nokutenda', 'Tadiwanashe', 'Rutendo'];
        $surnames = ['Moyo', 'Sibanda', 'Ndlovu', 'Dube', 'Mutasa', 'Gumbo', 'Zhou', 'Shumba', 'Mpofu', 'Maphosa'];
        $houses = ['Nyanga', 'Chiadzwa', 'Chimanimani', 'Vumba'];
        $bloodGroups = ['O+', 'O-', 'A+', 'B+', 'AB+'];
        $medicalNotes = ['None', 'Mild pollen allergy', 'Requires asthma inhaler near sports field', 'None', 'None'];

        foreach ($sections as $section) {
            // Resolve course relation manually to prevent null-errors in collection loops
            $course = $section->course ?? Course::find($section->course_id);
            if (! $course) {
                continue;
            }

            $log("Seeding exactly 10 students into stream: {$course->name} {$section->name}...");

            // Define custom test plans for this course & subject
            $plan = AssessmentPlan::firstOrCreate([
                'school_id' => $schoolId,
                'term_id' => $term->id,
                'course_id' => $course->id,
                'subject_id' => $subject->id,
                'created_by_id' => 1,
            ]);

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
                'created_by_id' => 1,
            ]);

            $assessmentExam = Assessment::firstOrCreate([
                'school_id' => $schoolId,
                'assessment_plan_component_id' => $compExam->id,
                'section_id' => $section->id,
                'name' => 'Term 1 Main Examination',
            ], [
                'assessment_date' => now()->subDays(2),
                'max_mark' => 100.00,
                'included_in_report' => true,
                'status' => 'locked',
                'created_by_id' => 1,
            ]);

            // Calculate precise age based on course level name
            $courseName = $course->name;
            $age = 6; // default

            if (stripos($courseName, 'ECD A') !== false) {
                $age = 4;
            } elseif (stripos($courseName, 'ECD B') !== false) {
                $age = 5;
            } elseif (preg_match('/Grade\s*([1-7])/i', $courseName, $matches)) {
                $age = 5 + intval($matches[1]);
            } elseif (preg_match('/Form\s*([1-4])/i', $courseName, $matches)) {
                $age = 12 + intval($matches[1]);
            } elseif (stripos($courseName, 'Lower Six') !== false || stripos($courseName, 'Lower 6') !== false) {
                $age = 17;
            } elseif (stripos($courseName, 'Upper Six') !== false || stripos($courseName, 'Upper 6') !== false) {
                $age = 18;
            }

            // Create EXACTLY 10 highly realistic students per section
            for ($k = 0; $k < 10; $k++) {
                $studentCount++;
                $gender = $k % 2 === 0 ? 'female' : 'male';
                $firstName = $gender === 'female'
                    ? $firstNamesFemale[rand(0, 9)]
                    : $firstNamesMale[rand(0, 9)];
                $surname = $surnames[rand(0, 9)];

                $dob = now()->subYears($age)->subDays(rand(1, 280));

                // Formulate unique IDs using the count variable to prevent duplicate errors
                $stuIdNumber = 'TEST-STU-'.str_pad($studentCount, 5, '0', STR_PAD_LEFT);
                $admNumber = 'TEST-ADM-'.date('y').str_pad($studentCount, 4, '0', STR_PAD_LEFT);

                $student = Student::create([
                    'school_id' => $schoolId,
                    'student_id_number' => $stuIdNumber,
                    'admission_number' => $admNumber,
                    'first_name' => $firstName,
                    'last_name' => $surname,
                    'gender' => $gender,
                    'date_of_birth' => $dob,
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

                // Enrollment mapping
                $enrollment = Enrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                ]);

                // Populate Assessment Marks
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

                // Generate fully filled Academic Report
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
                    'unhu_competencies' => [
                        'respect' => 'excellent',
                        'honesty' => 'very_good',
                        'responsibility' => 'excellent',
                        'discipline' => 'excellent',
                        'patriotism' => 'satisfactory',
                        'cooperation' => 'very_good',
                        'leadership' => 'satisfactory',
                        'critical_thinking' => 'very_good',
                        'creativity' => 'excellent',
                        'environment' => 'needs_improvement',
                        'communication' => 'very_good',
                        'digital_literacy' => 'satisfactory',
                        'entrepreneurship' => 'satisfactory',
                        'cultural_appreciation' => 'very_good',
                        'community_service' => 'outstanding',
                        'perseverance' => 'excellent',
                        'compassion' => 'very_good',
                        'time_management' => 'excellent',
                        'self_confidence' => 'excellent',
                        'adaptability' => 'very_good',
                        'outstanding_achievements' => "★ Outstanding Academic Performance Award\n★ Captain of the Sports Athletics Team",
                    ],
                ]);

                $reportCount++;
            }
        }

        return [
            'students' => $studentCount,
            'reports' => $reportCount,
            'sections' => $sections->count(),
        ];
    }

    /**
     * Wipe all demonstration records previously seeded by this service.
     *
     * @return int number of student rows removed
     */
    public function wipe(int $schoolId): int
    {
        $testStudents = Student::where('school_id', $schoolId)->where('student_id_number', 'LIKE', 'TEST-STU-%')->get();
        $studentIds = $testStudents->pluck('id')->toArray();

        if (empty($studentIds)) {
            return 0;
        }

        AcademicReport::whereIn('student_id', $studentIds)->delete();
        $enrollmentIds = Enrollment::whereIn('student_id', $studentIds)->pluck('id')->toArray();
        AssessmentMarksLedger::whereIn('enrollment_id', $enrollmentIds)->delete();
        Enrollment::whereIn('student_id', $studentIds)->delete();

        return Student::whereIn('id', $studentIds)->forceDelete();
    }
}
