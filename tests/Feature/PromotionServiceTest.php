<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Promotion\PromotionService;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Promotion\Models\PromotionItem;
use Modules\Promotion\Models\PromotionRun;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
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
            $tag = 'PSV_';
            DB::connection('mysql')->table('promotion_items')
                ->where('school_id', $this->school->id)
                ->delete();
            DB::connection('mysql')->table('promotion_runs')
                ->where('school_id', $this->school->id)
                ->where('source_academic_year_id', '>', 0)
                ->delete();

            $psvStudentIds = DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag.'%')
                ->pluck('id');

            DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $psvStudentIds)
                ->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag.'%')
                ->delete();

            $testCourseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->pluck('id');

            DB::connection('mysql')->table('sections')
                ->whereIn('course_id', $testCourseIds)
                ->delete();

            DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->delete();

            DB::connection('mysql')->table('academic_years')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag.'%')
                ->delete();
        }

        parent::tearDown();
    }

    private function makeYear(string $suffix): AcademicYear
    {
        return AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'PSV_'.$suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);
    }

    private function makeCourse(string $name, ?int $nextLevelId = null, bool $isTerminal = false): Course
    {
        return Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $name.'_'.uniqid('', true),
            'code' => strtoupper(substr(md5($name.uniqid('', true)), 0, 6)),
            'next_level_id' => $nextLevelId,
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
            'first_name' => 'PSV_'.$suffix,
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

    public function test_commit_marks_student_graduated_when_promoted_from_terminal_level(): void
    {
        $sourceYear = $this->makeYear('SrcG');
        $targetYear = $this->makeYear('TgtG');
        $terminalCourse = $this->makeCourse('PSV_Form6', null, true);
        $secA = $this->makeSection($terminalCourse->id, 'A');
        $student = $this->makeStudent('Grad1');
        $this->makeEnrollment($student, $terminalCourse->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $student->refresh();
        $this->assertSame('graduated', $student->status);

        $newEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $targetYear->id)
            ->first();

        $this->assertNull($newEnrollment, 'graduated students get no target enrollment');
    }

    public function test_undo_restores_student_to_active_after_graduation(): void
    {
        $sourceYear = $this->makeYear('SrcU');
        $targetYear = $this->makeYear('TgtU');
        $terminalCourse = $this->makeCourse('PSV_Form6', null, true);
        $secA = $this->makeSection($terminalCourse->id, 'A');
        $student = $this->makeStudent('Undo1');
        $this->makeEnrollment($student, $terminalCourse->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $this->assertSame('graduated', $student->refresh()->status);

        $service->undo($run->id);

        $this->assertSame('active', $student->refresh()->status);
    }

    public function test_preview_creates_run_with_draft_status(): void
    {
        $sourceYear = $this->makeYear('SrcY');
        $targetYear = $this->makeYear('TgtY');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $student = $this->makeStudent('Prev1');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $this->assertInstanceOf(PromotionRun::class, $run);
        $this->assertSame(PromotionRun::STATUS_DRAFT, $run->status);
        $this->assertCount(1, $run->items);
    }

    public function test_preview_promotes_student_to_next_level(): void
    {
        $sourceYear = $this->makeYear('Src2');
        $targetYear = $this->makeYear('Tgt2');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $student = $this->makeStudent('Prev2');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $item = $run->items->first();
        $this->assertSame(PromotionItem::DECISION_PROMOTED, $item->decision);
        $this->assertSame($courseB->id, $item->target_course_id);
        $this->assertSame($secB->id, $item->target_section_id);
    }

    public function test_preview_marks_terminal_level_as_needs_screening(): void
    {
        $sourceYear = $this->makeYear('Src3');
        $targetYear = $this->makeYear('Tgt3');
        $terminalCourse = $this->makeCourse('PSV_Form6', null, true);
        $secA = $this->makeSection($terminalCourse->id, 'A');
        $student = $this->makeStudent('Prev3');
        $this->makeEnrollment($student, $terminalCourse->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $item = $run->items->first();
        $this->assertSame(PromotionItem::DECISION_NEEDS_SCREENING, $item->decision);
    }

    public function test_preview_summary_counts_by_decision(): void
    {
        $sourceYear = $this->makeYear('Src4');
        $targetYear = $this->makeYear('Tgt4');
        $courseA = $this->makeCourse('PSV_Form4');
        $courseB = $this->makeCourse('PSV_Form5');
        $courseA->update(['next_level_id' => $courseB->id]);
        $courseTerm = $this->makeCourse('PSV_Form6', null, true);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $secT = $this->makeSection($courseTerm->id, 'A');

        $s1 = $this->makeStudent('Prev4a');
        $s2 = $this->makeStudent('Prev4b');
        $s3 = $this->makeStudent('Prev4c');
        $this->makeEnrollment($s1, $courseA->id, $secA->id, $sourceYear->id);
        $this->makeEnrollment($s2, $courseA->id, $secA->id, $sourceYear->id);
        $this->makeEnrollment($s3, $courseTerm->id, $secT->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $summary = $run->previewSummary();
        $this->assertSame(2, $summary['promoted']);
        $this->assertSame(1, $summary['needs_screening']);
        $this->assertSame(3, $summary['total']);
    }

    public function test_commit_creates_new_enrollments_in_target_year(): void
    {
        $sourceYear = $this->makeYear('Src5');
        $targetYear = $this->makeYear('Tgt5');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $student = $this->makeStudent('Cmt1');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $newEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $targetYear->id)
            ->first();

        $this->assertNotNull($newEnrollment);
        $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);
        $this->assertSame($courseB->id, $newEnrollment->course_id);
        $this->assertSame($secB->id, $newEnrollment->section_id);

        $oldEnrollment = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $sourceYear->id)
            ->first();

        $this->assertSame(Enrollment::STATUS_PROMOTED, $oldEnrollment->status);
    }

    public function test_commit_marks_run_as_committed_with_timestamp(): void
    {
        $sourceYear = $this->makeYear('Src6');
        $targetYear = $this->makeYear('Tgt6');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $student = $this->makeStudent('Cmt2');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $run->refresh();
        $this->assertSame(PromotionRun::STATUS_COMMITTED, $run->status);
        $this->assertNotNull($run->committed_at);
    }

    public function test_commit_skips_needs_screening_items(): void
    {
        $sourceYear = $this->makeYear('Src7');
        $targetYear = $this->makeYear('Tgt7');
        $terminalCourse = $this->makeCourse('PSV_Form6', null, true);
        $secA = $this->makeSection($terminalCourse->id, 'A');
        $student = $this->makeStudent('Cmt3');
        $this->makeEnrollment($student, $terminalCourse->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
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
        $sourceYear = $this->makeYear('Src8');
        $targetYear = $this->makeYear('Tgt8');
        $courseA = $this->makeCourse('PSV_Form1');
        $secA = $this->makeSection($courseA->id, 'A');
        $student = $this->makeStudent('Cmt4');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $this->expectException(\RuntimeException::class);
        $service->commit($run->id);
    }

    public function test_preview_parallel_section_fallback_to_first(): void
    {
        $sourceYear = $this->makeYear('Src9');
        $targetYear = $this->makeYear('Tgt9');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secBx = $this->makeSection($courseB->id, 'X');
        $secBy = $this->makeSection($courseB->id, 'Y');
        $student = $this->makeStudent('Fall1');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $item = $run->items->first();
        $this->assertSame(PromotionItem::DECISION_PROMOTED, $item->decision);
        $this->assertNotNull($item->target_section_id);
    }

    public function test_preview_empty_year_creates_empty_run(): void
    {
        $sourceYear = $this->makeYear('Src10');
        $targetYear = $this->makeYear('Tgt10');

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $this->assertCount(0, $run->items);
        $this->assertSame(0, $run->previewSummary()['total']);
    }

    public function test_preview_excludes_students_with_blank_or_deleted_names(): void
    {
        $sourceYear = $this->makeYear('SrcBl');
        $targetYear = $this->makeYear('TgtBl');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $named = $this->makeStudent('Bln1');
        $this->makeEnrollment($named, $courseA->id, $secA->id, $sourceYear->id);

        $blankLastName = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'PSV_Bland',
            'last_name' => '',
            'gender' => 'Male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        $this->makeEnrollment($blankLastName, $courseA->id, $secA->id, $sourceYear->id);

        $deleted = $this->makeStudent('Bln2');
        $deleted->delete();
        $this->makeEnrollment($deleted, $courseA->id, $secA->id, $sourceYear->id);

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);

        $this->assertCount(1, $run->items);
        $item = $run->items->first();
        $this->assertSame($named->id, $item->student_id);
        $this->assertSame($courseB->id, $item->target_course_id);
        $this->assertSame($secB->id, $item->target_section_id);
    }

    public function test_commit_does_not_create_intake_students(): void
    {
        $sourceYear = $this->makeYear('SrcIc');
        $targetYear = $this->makeYear('TgtIc');
        $courseA = $this->makeCourse('PSV_Form1');
        $courseB = $this->makeCourse('PSV_Form2');
        $courseA->update(['next_level_id' => $courseB->id]);
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');
        $student = $this->makeStudent('Intk1');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $sourceYear->id);

        $countBefore = Student::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->count();

        $service = new PromotionService;
        $run = $service->preview($this->school->id, $sourceYear->id, $targetYear->id);
        $service->commit($run->id);

        $countAfter = Student::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->count();

        $this->assertSame($countBefore, $countAfter);
    }
}
