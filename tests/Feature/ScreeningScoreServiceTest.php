<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Screening\ScreeningScoreService;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ScreeningScoreServiceTest extends TestCase
{
    use InteractsWithDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));

        $this->school = School::where('subdomain', 'chiwariraprimary')->first();
        $this->assertTrue($this->school->id > 0, 'School chiwariraprimary must exist');

        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        if (isset($this->school)) {
            $tag = 'SCO_';
            DB::connection('mysql')->table('assessment_marks')->where('school_id', $this->school->id)
                ->whereIn('enrollment_id', function ($q) use ($tag) {
                    $q->select('id')->from('enrollments')
                        ->whereIn('student_id', function ($q2) use ($tag) {
                            $q2->select('id')->from('students')
                                ->where('school_id', $this->school->id)
                                ->where('first_name', 'like', $tag . '%');
                        });
                })->delete();

            DB::connection('mysql')->table('enrollments')
                ->where('school_id', $this->school->id)
                ->whereIn('student_id', function ($q) use ($tag) {
                    $q->select('id')->from('students')
                        ->where('school_id', $this->school->id)
                        ->where('first_name', 'like', $tag . '%');
                })->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('terms')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('assessment_types')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('sections')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('subjects')
                ->where('school_id', $this->school->id)
                ->where('code', 'like', '%' . substr($tag, 0, 4) . '%')
                ->delete();

            $psvCourseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('sections')
                ->whereIn('course_id', $psvCourseIds)
                ->delete();

            DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();

            DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->delete();
        }

        parent::tearDown();
    }

    private function makeYear(string $suffix): AcademicYear
    {
        $year = AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'SCO_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);

        Term::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'SCO_Term1_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
            'is_active' => false,
        ]);

        return $year;
    }

    private function makeCourse(string $name): Course
    {
        return Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $name . '_' . uniqid('', true),
            'code' => strtoupper(substr(md5($name . uniqid('', true)), 0, 6)),
        ]);
    }

    private function makeSection(int $courseId, string $name): Section
    {
        return Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $courseId,
            'name' => $name,
            'capacity' => 40,
            'target_size' => 40,
        ]);
    }

    private function makeSubject(string $code): Subject
    {
        return Subject::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $code . ' Subject',
            'code' => 'SCO' . $code . substr(uniqid('', true), -4),
        ]);
    }

    private function makeStudent(string $suffix): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'SCO_' . $suffix,
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    private function makeEnrollment(Student $student, int $courseId, int $sectionId, int $yearId): Enrollment
    {
        return Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearId,
            'course_id' => $courseId,
            'section_id' => $sectionId,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => '2026-09-01',
        ]);
    }

    private function makeAssessmentType(string $name, float $maxMark, float $weight): AssessmentType
    {
        $term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('name', 'like', 'SCO_Term1_%')
            ->first();

        if (! $term) {
            $year = AcademicYear::withoutGlobalScopes()
                ->where('school_id', $this->school->id)
                ->where('name', 'like', 'SCO_%')
                ->orderByDesc('id')
                ->first();

            $term = Term::withoutGlobalScopes()->create([
                'school_id' => $this->school->id,
                'academic_year_id' => $year?->id,
                'name' => 'SCO_Term1_' . uniqid('', true),
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-20',
                'is_active' => false,
            ]);
        }

        $user = \App\Models\User::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->first();

        return AssessmentType::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'term_id' => $term->id,
            'name' => $name . '_' . uniqid('', true),
            'max_mark' => $maxMark,
            'weight_percentage' => $weight,
            'created_by_id' => $user?->id,
            'status' => 'active',
        ]);
    }

    private function makeMark(int $enrollmentId, int $assessmentTypeId, int $subjectId, float $marks): AssessmentMark
    {
        return AssessmentMark::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $enrollmentId,
            'assessment_type_id' => $assessmentTypeId,
            'subject_id' => $subjectId,
            'marks_obtained' => $marks,
        ]);
    }

    public function test_percentage_computes_marks_over_max(): void
    {
        $this->makeYear('PctY');
        $type = $this->makeAssessmentType('SCO_CalcPct', 50.0, 100.0);
        $service = new ScreeningScoreService();

        $this->assertSame(80.0, $service->percentage(40.0, $type));
        $this->assertNull($service->percentage(null, $type));
    }

    public function test_subject_score_weighted_average(): void
    {
        $year = $this->makeYear('SubjY');
        $course = $this->makeCourse('SCO_Level');
        $section = $this->makeSection($course->id, 'A');
        $student = $this->makeStudent('Subj1');
        $enrollment = $this->makeEnrollment($student, $course->id, $section->id, $year->id);
        $subject = $this->makeSubject('Math');

        $typeA = $this->makeAssessmentType('SCO_CAT', 50.0, 30.0);
        $typeB = $this->makeAssessmentType('SCO_EOT', 100.0, 70.0);

        $this->makeMark($enrollment->id, $typeA->id, $subject->id, 40.0);   // 80% @ weight 30
        $this->makeMark($enrollment->id, $typeB->id, $subject->id, 90.0);   // 90% @ weight 70

        $service = new ScreeningScoreService();
        $score = $service->subjectScore($enrollment->id, $subject->id);

        $expected = (80 * 30 + 90 * 70) / 100; // 87.0
        $this->assertNotNull($score);
        $this->assertEqualsWithDelta($expected, $score, 0.001);
    }

    public function test_overall_score_is_mean_of_subject_scores(): void
    {
        $year = $this->makeYear('OverY');
        $course = $this->makeCourse('SCO_Level');
        $section = $this->makeSection($course->id, 'A');
        $student = $this->makeStudent('Over1');
        $enrollment = $this->makeEnrollment($student, $course->id, $section->id, $year->id);

        $math = $this->makeSubject('Math');
        $eng = $this->makeSubject('Eng');

        $type = $this->makeAssessmentType('SCO_Exam', 100.0, 100.0);

        $this->makeMark($enrollment->id, $type->id, $math->id, 80.0);
        $this->makeMark($enrollment->id, $type->id, $eng->id, 60.0);

        $service = new ScreeningScoreService();
        $overall = $service->overallScore($student->id, $year->id);

        $this->assertNotNull($overall);
        $this->assertEqualsWithDelta(70.0, $overall, 0.001);
    }

    public function test_overall_score_returns_null_when_no_marks(): void
    {
        $year = $this->makeYear('NoMkY');
        $course = $this->makeCourse('SCO_Level');
        $section = $this->makeSection($course->id, 'A');
        $student = $this->makeStudent('NoMk1');
        $this->makeEnrollment($student, $course->id, $section->id, $year->id);

        $service = new ScreeningScoreService();
        $this->assertNull($service->overallScore($student->id, $year->id));
    }

    public function test_student_screening_profile_includes_subject_scores(): void
    {
        $year = $this->makeYear('ProfY');
        $course = $this->makeCourse('SCO_Level');
        $section = $this->makeSection($course->id, 'A');
        $student = $this->makeStudent('Prof1');
        $enrollment = $this->makeEnrollment($student, $course->id, $section->id, $year->id);

        $math = $this->makeSubject('Math');
        $type = $this->makeAssessmentType('SCO_Exam', 100.0, 100.0);
        $this->makeMark($enrollment->id, $type->id, $math->id, 75.0);

        $service = new ScreeningScoreService();
        $profile = $service->studentScreeningProfile($student->id, $year->id);

        $this->assertSame($enrollment->id, $profile['enrollment_id']);
        $this->assertCount(1, $profile['subject_scores']);
        $this->assertArrayHasKey($math->id, $profile['subject_scores']);
        $this->assertEqualsWithDelta(75.0, $profile['subject_scores'][$math->id]['score'], 0.001);
        $this->assertEqualsWithDelta(75.0, $profile['overall_score'], 0.001);
    }

    public function test_cohort_scores_keyed_by_student_id(): void
    {
        $year = $this->makeYear('CohY');
        $course = $this->makeCourse('SCO_Level');
        $section = $this->makeSection($course->id, 'A');

        $s1 = $this->makeStudent('Coh1');
        $s2 = $this->makeStudent('Coh2');

        $e1 = $this->makeEnrollment($s1, $course->id, $section->id, $year->id);
        $e2 = $this->makeEnrollment($s2, $course->id, $section->id, $year->id);

        $math = $this->makeSubject('Math');
        $type = $this->makeAssessmentType('SCO_Exam', 100.0, 100.0);

        $this->makeMark($e1->id, $type->id, $math->id, 90.0);
        $this->makeMark($e2->id, $type->id, $math->id, 50.0);

        $service = new ScreeningScoreService();
        $scores = $service->cohortScores(collect([$s1, $s2]), $year->id);

        $this->assertCount(2, $scores);
        $this->assertEqualsWithDelta(90.0, $scores[$s1->id], 0.001);
        $this->assertEqualsWithDelta(50.0, $scores[$s2->id], 0.001);
    }
}