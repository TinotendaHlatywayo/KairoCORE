<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Academic\ClassAssignmentService;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ClassAssignmentServiceTest extends TestCase
{
    use InteractsWithDatabase;

    private School $school;

    private AcademicYear $year;

    private Course $course;

    private Section $sectionA;

    private Section $sectionB;

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

        $tag = uniqid('CAS_', true);

        $this->year = AcademicYear::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $tag.' Year',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => false,
        ]);

        $this->course = Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $tag.' Level',
            'code' => strtoupper(substr($tag, 0, 6)),
        ]);

        $this->sectionA = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $this->course->id,
            'name' => 'A',
            'capacity' => 40,
            'target_size' => 40,
        ]);

        $this->sectionB = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $this->course->id,
            'name' => 'B',
            'capacity' => 40,
            'target_size' => 40,
        ]);

        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        if (isset($this->school)) {
            DB::connection('mysql')->table('enrollments')
                ->where('school_id', $this->school->id)
                ->where('academic_year_id', $this->year?->id ?? 0)
                ->delete();

            DB::connection('mysql')->table('students')
                ->where('school_id', $this->school->id)
                ->where('first_name', 'like', 'CASTest_%')
                ->delete();

            if (isset($this->sectionA)) {
                DB::connection('mysql')->table('sections')->where('id', $this->sectionA->id)->delete();
            }
            if (isset($this->sectionB)) {
                DB::connection('mysql')->table('sections')->where('id', $this->sectionB->id)->delete();
            }
            if (isset($this->course)) {
                DB::connection('mysql')->table('courses')->where('id', $this->course->id)->delete();
            }
            if (isset($this->year)) {
                DB::connection('mysql')->table('academic_years')->where('id', $this->year->id)->delete();
            }
        }

        parent::tearDown();
    }

    private function createStudent(string $suffix): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'first_name' => 'CASTest_'.$suffix,
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    private function createEnrollment(Student $student, Section $section, string $status = Enrollment::STATUS_ACTIVE): Enrollment
    {
        return Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'status' => $status,
            'effective_date' => $this->year->start_date,
        ]);
    }

    public function test_reassign_moves_student_from_section_a_to_b(): void
    {
        $student = $this->createStudent('MoveA');
        $originalEnrollment = $this->createEnrollment($student, $this->sectionA);

        $service = new ClassAssignmentService;
        $newEnrollment = $service->reassign(
            $student->id,
            $this->sectionA->id,
            $this->sectionB->id,
            $this->year->id,
            'Parent request',
        );

        $this->assertNotSame($originalEnrollment->id, $newEnrollment->id);
        $this->assertSame($this->sectionB->id, $newEnrollment->section_id);
        $this->assertSame($this->course->id, $newEnrollment->course_id);
        $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);
        $this->assertNotNull($newEnrollment->effective_date);

        $originalEnrollment->refresh();
        $this->assertSame(Enrollment::STATUS_TRANSFERRED_OUT, $originalEnrollment->status);
        $this->assertSame('Parent request', $originalEnrollment->reason);
    }

    public function test_reassign_preserves_enrollment_history(): void
    {
        $student = $this->createStudent('HistA');
        $this->createEnrollment($student, $this->sectionA);

        $service = new ClassAssignmentService;
        $service->reassign(
            $student->id,
            $this->sectionA->id,
            $this->sectionB->id,
            $this->year->id,
        );

        $allEnrollments = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $this->year->id)
            ->get();

        $this->assertCount(2, $allEnrollments);

        $statuses = $allEnrollments->pluck('status')->values()->all();
        $this->assertContains(Enrollment::STATUS_ACTIVE, $statuses);
        $this->assertContains(Enrollment::STATUS_TRANSFERRED_OUT, $statuses);
    }

    public function test_reassign_fails_when_student_not_in_source_section(): void
    {
        $student = $this->createStudent('FailA');
        $this->createEnrollment($student, $this->sectionA);

        $service = new ClassAssignmentService;

        $this->expectException(\RuntimeException::class);
        $service->reassign(
            $student->id,
            $this->sectionB->id,
            $this->sectionA->id,
            $this->year->id,
        );
    }

    public function test_reassign_fails_when_section_full(): void
    {
        $tinySection = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $this->course->id,
            'name' => 'Tiny',
            'capacity' => 1,
            'target_size' => 1,
        ]);

        $s1 = $this->createStudent('Full1');
        $s2 = $this->createStudent('Full2');
        $this->createEnrollment($s1, $this->sectionA);
        $this->createEnrollment($s2, $tinySection);

        $service = new ClassAssignmentService;

        $this->expectException(\OverflowException::class);
        $service->reassign(
            $s1->id,
            $this->sectionA->id,
            $tinySection->id,
            $this->year->id,
        );

        DB::connection('mysql')->table('sections')->where('id', $tinySection->id)->delete();
    }

    public function test_reassign_same_section_throws(): void
    {
        $student = $this->createStudent('SameSec');
        $this->createEnrollment($student, $this->sectionA);

        $service = new ClassAssignmentService;

        $this->expectException(\InvalidArgumentException::class);
        $service->reassign(
            $student->id,
            $this->sectionA->id,
            $this->sectionA->id,
            $this->year->id,
        );
    }

    public function test_reassign_cross_level_throws(): void
    {
        $otherCourse = Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => 'CAS_Other_'.uniqid(),
            'code' => strtoupper('OT'.uniqid('', true)),
        ]);
        $otherSection = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $otherCourse->id,
            'name' => 'A',
            'capacity' => 40,
            'target_size' => 40,
        ]);

        $student = $this->createStudent('CrossLevel');
        $this->createEnrollment($student, $this->sectionA);

        $service = new ClassAssignmentService;

        $this->expectException(\InvalidArgumentException::class);

        // The finally block guarantees the throwaway course/section are removed
        // even after the expected exception, otherwise every test run leaks a
        // junk "CAS_Other_<unique-id>" course into the demo school's roster.
        try {
            $service->reassign(
                $student->id,
                $this->sectionA->id,
                $otherSection->id,
                $this->year->id,
            );
        } finally {
            DB::connection('mysql')->table('sections')->where('id', $otherSection->id)->delete();
            DB::connection('mysql')->table('courses')->where('id', $otherCourse->id)->delete();
        }
    }

    public function test_reassign_bulk_moves_multiple_students(): void
    {
        $s1 = $this->createStudent('Bulk1');
        $s2 = $this->createStudent('Bulk2');
        $s3 = $this->createStudent('Bulk3');
        $this->createEnrollment($s1, $this->sectionA);
        $this->createEnrollment($s2, $this->sectionA);
        $this->createEnrollment($s3, $this->sectionA);

        $service = new ClassAssignmentService;
        $results = $service->reassignBulk(
            [$s1->id, $s2->id, $s3->id],
            $this->sectionA->id,
            $this->sectionB->id,
            $this->year->id,
            'Class restructure',
        );

        $this->assertCount(3, $results);

        foreach ($results as $newEnrollment) {
            $this->assertSame($this->sectionB->id, $newEnrollment->section_id);
            $this->assertSame(Enrollment::STATUS_ACTIVE, $newEnrollment->status);
        }

        $oldEnrollments = Enrollment::withoutGlobalScopes()
            ->whereIn('student_id', [$s1->id, $s2->id, $s3->id])
            ->where('section_id', $this->sectionA->id)
            ->where('academic_year_id', $this->year->id)
            ->get();

        $this->assertCount(3, $oldEnrollments);
        $this->assertCount(3, $oldEnrollments->where('status', Enrollment::STATUS_TRANSFERRED_OUT));
    }

    public function test_reassign_bulk_fails_when_any_student_missing(): void
    {
        $s1 = $this->createStudent('BulkMissing1');
        $this->createEnrollment($s1, $this->sectionA);

        $service = new ClassAssignmentService;

        $this->expectException(\RuntimeException::class);
        $service->reassignBulk(
            [$s1->id, 99999999],
            $this->sectionA->id,
            $this->sectionB->id,
            $this->year->id,
        );
    }

    public function test_reassign_bulk_fails_when_would_exceed_capacity(): void
    {
        $tiny = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $this->course->id,
            'name' => 'TinyBulk',
            'capacity' => 2,
            'target_size' => 2,
        ]);

        $s1 = $this->createStudent('CapB1');
        $s2 = $this->createStudent('CapB2');
        $s3 = $this->createStudent('CapB3');
        $this->createEnrollment($s1, $this->sectionA);
        $this->createEnrollment($s2, $this->sectionA);
        $this->createEnrollment($s3, $this->sectionA);

        $service = new ClassAssignmentService;

        $this->expectException(\OverflowException::class);
        $service->reassignBulk(
            [$s1->id, $s2->id, $s3->id],
            $this->sectionA->id,
            $tiny->id,
            $this->year->id,
        );

        DB::connection('mysql')->table('sections')->where('id', $tiny->id)->delete();
    }

    public function test_reassign_bulk_deduplicates_student_ids(): void
    {
        $s1 = $this->createStudent('Dedup1');
        $this->createEnrollment($s1, $this->sectionA);

        $service = new ClassAssignmentService;
        $results = $service->reassignBulk(
            [$s1->id, $s1->id],
            $this->sectionA->id,
            $this->sectionB->id,
            $this->year->id,
        );

        $this->assertCount(1, $results);
    }
}
