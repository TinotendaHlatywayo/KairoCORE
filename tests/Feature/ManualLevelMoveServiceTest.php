<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Promotion\ManualLevelMoveService;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ManualLevelMoveServiceTest extends TestCase
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
            $tag = 'MLM_';
            $mlmStudentIds = DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('enrollments')
                ->whereIn('student_id', $mlmStudentIds)
                ->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', $tag . '%')
                ->delete();

            $mlmCourseIds = DB::connection('mysql')->table('courses')
                ->where('school_id', $this->school->id)
                ->where('name', 'like', $tag . '%')
                ->pluck('id');

            DB::connection('mysql')->table('sections')
                ->whereIn('course_id', $mlmCourseIds)
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
        return AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'MLM_' . $suffix,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);
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

    private function makeStudent(string $suffix): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'MLM_' . $suffix,
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

    public function test_move_single_student_to_next_level(): void
    {
        $srcYear = $this->makeYear('Src1');
        $tgtYear = $this->makeYear('Tgt1');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('One');
        $oldEnrollment = $this->makeEnrollment($student, $courseA->id, $secA->id, $srcYear->id);

        $service = new ManualLevelMoveService();
        $newEnrollment = $service->move(
            $student->id,
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
            'Skip-ahead to Form 2',
        );

        $this->assertSame($tgtYear->id, $newEnrollment->academic_year_id);
        $this->assertSame($courseB->id, $newEnrollment->course_id);
        $this->assertSame($secB->id, $newEnrollment->section_id);
        $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);

        $oldEnrollment->refresh();
        $this->assertSame(Enrollment::STATUS_PROMOTED, $oldEnrollment->status);
        $this->assertSame('Skip-ahead to Form 2', $oldEnrollment->reason);
    }

    public function test_move_bulk_moves_multiple_students(): void
    {
        $srcYear = $this->makeYear('Src2');
        $tgtYear = $this->makeYear('Tgt2');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $s1 = $this->makeStudent('B1');
        $s2 = $this->makeStudent('B2');
        $s3 = $this->makeStudent('B3');
        $this->makeEnrollment($s1, $courseA->id, $secA->id, $srcYear->id);
        $this->makeEnrollment($s2, $courseA->id, $secA->id, $srcYear->id);
        $this->makeEnrollment($s3, $courseA->id, $secA->id, $srcYear->id);

        $service = new ManualLevelMoveService();
        $results = $service->moveBulk(
            [$s1->id, $s2->id, $s3->id],
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
        );

        $this->assertCount(3, $results);

        foreach ($results as $e) {
            $this->assertSame($courseB->id, $e->course_id);
            $this->assertSame($secB->id, $e->section_id);
            $this->assertSame($tgtYear->id, $e->academic_year_id);
        }
    }

    public function test_move_skips_already_enrolled_in_target_year(): void
    {
        $srcYear = $this->makeYear('Src3');
        $tgtYear = $this->makeYear('Tgt3');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('Dup');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $srcYear->id);
        $this->makeEnrollment($student, $courseB->id, $secB->id, $tgtYear->id);

        $service = new ManualLevelMoveService();

        $this->expectException(\RuntimeException::class);
        $service->move(
            $student->id,
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
        );
    }

    public function test_move_throws_when_no_active_source_enrollment(): void
    {
        $srcYear = $this->makeYear('Src4');
        $tgtYear = $this->makeYear('Tgt4');
        $courseB = $this->makeCourse('MLM_F2');
        $secB = $this->makeSection($courseB->id, 'A');

        // Student has no enrollments at all.
        $student = $this->makeStudent('None');

        $service = new ManualLevelMoveService();

        $this->expectException(\RuntimeException::class);
        $service->move(
            $student->id,
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
        );
    }

    public function test_move_rejects_section_not_in_target_course(): void
    {
        $srcYear = $this->makeYear('Src5');
        $tgtYear = $this->makeYear('Tgt5');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $student = $this->makeStudent('Bad');
        $this->makeEnrollment($student, $courseA->id, $secA->id, $srcYear->id);

        $service = new ManualLevelMoveService();

        $this->expectException(\InvalidArgumentException::class);
        $service->move(
            $student->id,
            $srcYear->id,
            $tgtYear->id,
            $courseA->id,
            $secB->id,
        );
    }

    public function test_moveBulk_rejects_when_over_capacity(): void
    {
        $srcYear = $this->makeYear('Src6');
        $tgtYear = $this->makeYear('Tgt6');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $courseB->id,
            'name' => 'Tiny',
            'capacity' => 2,
            'target_size' => 2,
        ]);

        $s1 = $this->makeStudent('Cap1');
        $s2 = $this->makeStudent('Cap2');
        $s3 = $this->makeStudent('Cap3');
        $this->makeEnrollment($s1, $courseA->id, $secA->id, $srcYear->id);
        $this->makeEnrollment($s2, $courseA->id, $secA->id, $srcYear->id);
        $this->makeEnrollment($s3, $courseA->id, $secA->id, $srcYear->id);

        $service = new ManualLevelMoveService();

        $this->expectException(\OverflowException::class);
        $service->moveBulk(
            [$s1->id, $s2->id, $s3->id],
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
        );
    }

    public function test_move_bulk_deduplicates_student_ids(): void
    {
        $srcYear = $this->makeYear('Src7');
        $tgtYear = $this->makeYear('Tgt7');
        $courseA = $this->makeCourse('MLM_F1');
        $courseB = $this->makeCourse('MLM_F2');
        $secA = $this->makeSection($courseA->id, 'A');
        $secB = $this->makeSection($courseB->id, 'A');

        $s1 = $this->makeStudent('D');
        $this->makeEnrollment($s1, $courseA->id, $secA->id, $srcYear->id);

        $service = new ManualLevelMoveService();
        $results = $service->moveBulk(
            [$s1->id, $s1->id],
            $srcYear->id,
            $tgtYear->id,
            $courseB->id,
            $secB->id,
        );

        $this->assertCount(1, $results);
    }
}