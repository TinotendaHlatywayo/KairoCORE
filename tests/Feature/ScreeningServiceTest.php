<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\Screening\ScreeningScoreService;
use App\Services\Screening\ScreeningService;
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
use Modules\Students\Models\ScreeningRule;
use Modules\Students\Models\Student;
use Modules\Screening\Models\ScreeningItem;
use Modules\Screening\Models\ScreeningRun;
use Tests\TestCase;

class ScreeningServiceTest extends TestCase
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

        $this->school = School::where('subdomain', 'tinwayacademy')->first();
        $this->assertTrue($this->school->id > 0, 'School tinwayacademy must exist');

        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        if (isset($this->school)) {
            $tag = 'SCR_';
            DB::connection('mysql')->table('screening_items')
                ->where('school_id', $this->school->id)
                ->delete();
            DB::connection('mysql')->table('screening_runs')
                ->where('school_id', $this->school->id)
                ->delete();

            $scrStudentIds = DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->pluck('id');

            $scrEnrollmentIds = DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $scrStudentIds)
                ->pluck('id');

            DB::connection('mysql')->table('promotion_items')
                ->whereIn('source_enrollment_id', $scrEnrollmentIds)
                ->delete();

            $scrYearIds = DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('promotion_runs')
                ->where('school_id', $this->school->id)
                ->whereIn('source_academic_year_id', $scrYearIds)
                ->orWhereIn('target_academic_year_id', $scrYearIds)
                ->delete();

            DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $scrStudentIds)
                ->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->delete();

            $scrCourseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            $scrEnrollmentIds2 = \Illuminate\Support\Collection::make($scrEnrollmentIds);

            DB::connection('mysql')->table('assessment_marks')
                ->whereIn('enrollment_id', $scrEnrollmentIds2)
                ->delete();

            DB::connection('mysql')->table('sections')
                ->whereIn('course_id', $scrCourseIds)
                ->delete();

            DB::connection('mysql')->table('screening_rules')
                ->where('school_id', $this->school->id)
                ->whereIn('source_course_id', $scrCourseIds)
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
            'name' => 'SCR_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);

        Term::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'SCR_Term1_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
            'is_active' => false,
        ]);

        return $year;
    }

    private function makeCourse(string $name, bool $isTerminal = false): Course
    {
        return Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $name . '_' . uniqid('', true),
            'code' => strtoupper(substr(md5($name . uniqid('', true)), 0, 6)),
            'is_terminal' => $isTerminal,
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

    private function makeStudent(string $suffix): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'SCR_' . $suffix,
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

    private function makeSubject(): Subject
    {
        return Subject::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'SCR_Subj_' . uniqid('', true),
            'code' => 'SCRX' . substr(uniqid('', true), -6),
        ]);
    }

    private function makeAssessmentType(): AssessmentType
    {
        $term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('name', 'like', 'SCR_Term1_%')
            ->first();

        $user = User::withoutGlobalScopes()->where('school_id', $this->school->id)->first();

        return AssessmentType::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'term_id' => $term->id,
            'name' => 'SCR_Exam_' . uniqid('', true),
            'max_mark' => 100,
            'weight_percentage' => 100,
            'created_by_id' => $user?->id,
            'status' => 'active',
        ]);
    }

    private function makeMark(int $enrollmentId, int $subjectId, float $mark): AssessmentMark
    {
        $type = $this->makeAssessmentType();

        return AssessmentMark::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $enrollmentId,
            'assessment_type_id' => $type->id,
            'subject_id' => $subjectId,
            'marks_obtained' => $mark,
        ]);
    }

    private function makeRule(int $sourceCourseId, int $targetCourseId, int $targetSectionId, int $minPercentage, string $type = 'overall_gpa', ?int $subjectId = null): ScreeningRule
    {
        return ScreeningRule::create([
            'school_id' => $this->school->id,
            'source_course_id' => $sourceCourseId,
            'target_course_id' => $targetCourseId,
            'target_section_id' => $targetSectionId,
            'rule_type' => $type,
            'subject_id' => $subjectId,
            'min_percentage' => $minPercentage,
        ]);
    }

    public function test_preview_creates_screening_run_and_places_students(): void
    {
        $sourceYear = $this->makeYear('SrcY');
        $targetYear = $this->makeYear('TgtY');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');
        $secB = $this->makeSection($targetCourse->id, 'B');

        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secB->id, 60);

        $topStudent = $this->makeStudent('Top');
        $e1 = $this->makeEnrollment($topStudent, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 90); // overall score 90 → secA

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $this->assertSame(ScreeningRun::STATUS_DRAFT, $run->status);
        $this->assertCount(1, $run->items);

        $item = $run->items->first();
        $this->assertSame(ScreeningItem::DECISION_PLACED, $item->decision);
        $this->assertSame($secA->id, $item->target_section_id);
        $this->assertEquals(90, $item->overall_score);
    }

    public function test_preview_unplaced_when_no_rule_matches(): void
    {
        $sourceYear = $this->makeYear('SrcN');
        $targetYear = $this->makeYear('TgtN');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');

        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $lowStudent = $this->makeStudent('Low');
        $e1 = $this->makeEnrollment($lowStudent, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 40); // overall score 40 → no match

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $item = $run->items->first();
        $this->assertSame(ScreeningItem::DECISION_UNPLACED, $item->decision);
        $this->assertNull($item->target_section_id);
    }

    public function test_preview_uses_subject_rules(): void
    {
        $sourceYear = $this->makeYear('SrcS');
        $targetYear = $this->makeYear('TgtS');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');
        $secB = $this->makeSection($targetCourse->id, 'B');

        $subject = $this->makeSubject();
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80, 'subject', $subject->id);
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secB->id, 60, 'subject', $subject->id);

        $student = $this->makeStudent('Subj');
        $e1 = $this->makeEnrollment($student, $sourceCourse->id, $secA->id, $sourceYear->id);
        $this->makeMark($e1->id, $subject->id, 85); // subject score 85 → secA

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $item = $run->items->first();
        $this->assertSame(ScreeningItem::DECISION_PLACED, $item->decision);
        $this->assertSame($secA->id, $item->target_section_id);
    }

    public function test_commit_creates_enrollment_in_target_year(): void
    {
        $sourceYear = $this->makeYear('SrcC');
        $targetYear = $this->makeYear('TgtC');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');

        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $student = $this->makeStudent('Cmt');
        $e1 = $this->makeEnrollment($student, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 90);

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $newEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $targetYear->id)
            ->first();

        $this->assertNotNull($newEnrollment);
        $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);
        $this->assertSame($targetCourse->id, $newEnrollment->course_id);
        $this->assertSame($secA->id, $newEnrollment->section_id);

        $oldEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $sourceYear->id)
            ->first();
        $this->assertSame(Enrollment::STATUS_PROMOTED, $oldEnrollment->status);

        $run->refresh();
        $this->assertSame(ScreeningRun::STATUS_COMMITTED, $run->status);
        $this->assertNotNull($run->committed_at);
    }

    public function test_commit_skips_unplaced_items(): void
    {
        $sourceYear = $this->makeYear('SrcU');
        $targetYear = $this->makeYear('TgtU');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');

        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $student = $this->makeStudent('Unpl');
        $e1 = $this->makeEnrollment($student, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 30);

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $newEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $targetYear->id)
            ->first();

        $this->assertNull($newEnrollment);
    }

    public function test_commit_throws_for_non_draft_run(): void
    {
        $sourceYear = $this->makeYear('SrcR');
        $targetYear = $this->makeYear('TgtR');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $student = $this->makeStudent('Rej');
        $e1 = $this->makeEnrollment($student, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 90);

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $this->expectException(\RuntimeException::class);
        $service->commit($run->id);
    }

    public function test_preview_summary_counts_placed_and_unplaced(): void
    {
        $sourceYear = $this->makeYear('SrcSm');
        $targetYear = $this->makeYear('TgtSm');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $sTop = $this->makeStudent('SmTop');
        $eTop = $this->makeEnrollment($sTop, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($eTop->id, $subject->id, 90);

        $sLow = $this->makeStudent('SmLow');
        $eLow = $this->makeEnrollment($sLow, $sourceCourse->id, $secA->id, $sourceYear->id);
        $this->makeMark($eLow->id, $subject->id, 30);

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $summary = $run->previewSummary();
        $this->assertSame(1, $summary['placed']);
        $this->assertSame(1, $summary['unplaced']);
        $this->assertSame(2, $summary['total']);
    }

    public function test_preview_uses_promotion_run_needs_screening_candidates(): void
    {
        $sourceYear = $this->makeYear('SrcPR');
        $targetYear = $this->makeYear('TgtPR');
        $sourceCourse = $this->makeCourse('SCR_Form6', true);
        $targetCourse = $this->makeCourse('SCR_College');
        $secA = $this->makeSection($targetCourse->id, 'A');
        $this->makeRule($sourceCourse->id, $targetCourse->id, $secA->id, 80);

        $student = $this->makeStudent('Prom');
        $e1 = $this->makeEnrollment($student, $sourceCourse->id, $secA->id, $sourceYear->id);
        $subject = $this->makeSubject();
        $this->makeMark($e1->id, $subject->id, 90);

        // Simulate a promotion run with a needs_screening item for this student.
        $promoRun = \Modules\Promotion\Models\PromotionRun::create([
            'school_id' => $this->school->id,
            'source_academic_year_id' => $sourceYear->id,
            'target_academic_year_id' => $targetYear->id,
            'status' => 'draft',
        ]);

        \Modules\Promotion\Models\PromotionItem::create([
            'school_id' => $this->school->id,
            'promotion_run_id' => $promoRun->id,
            'student_id' => $student->id,
            'source_enrollment_id' => $e1->id,
            'decision' => 'needs_screening',
        ]);

        $service = new ScreeningService(new ScreeningScoreService());
        $run = $service->preview(
            $this->school->id,
            $sourceYear->id,
            $targetYear->id,
            $promoRun->id,
        );

        $this->assertCount(1, $run->items);
        $this->assertSame($promoRun->id, $run->promotion_run_id);
        $item = $run->items->first();
        $this->assertSame(ScreeningItem::DECISION_PLACED, $item->decision);
        $this->assertSame($secA->id, $item->target_section_id);
    }
}