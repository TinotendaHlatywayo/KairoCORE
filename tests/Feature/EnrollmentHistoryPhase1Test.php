<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class EnrollmentHistoryPhase1Test extends TestCase
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
        DB::purge('mysql');

        $this->school = School::where('subdomain', 'chiwariraprimary')->first() ?? School::first();
        $this->assertNotNull($this->school, 'A school record is required.');
        $this->actingAsTenant($this->school);
    }

    public function test_backfill_populated_status_and_effective_date_on_existing_enrollments()
    {
        $enrollments = Enrollment::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->get();

        $this->assertNotEmpty($enrollments, 'fixture school must have pre-existing enrollments');

        foreach ($enrollments as $enrollment) {
            $this->assertContains($enrollment->status, [
                Enrollment::STATUS_ACTIVE,
                Enrollment::STATUS_PROMOTED,
                Enrollment::STATUS_REPEATED,
                Enrollment::STATUS_TRANSFERRED_OUT,
                Enrollment::STATUS_GRADUATED,
            ], "enrollment {$enrollment->id} has a known status");
            $this->assertNotNull($enrollment->effective_date, "enrollment {$enrollment->id} has an effective date");
            $this->assertNotNull($enrollment->course_id, "enrollment {$enrollment->id} keeps its recorded level");
            $this->assertNotNull($enrollment->section_id, "enrollment {$enrollment->id} keeps its recorded class");
        }
    }

    public function test_current_enrollment_returns_the_backfilled_class_for_each_student()
    {
        $students = Student::withoutGlobalScopes()->where('school_id', $this->school->id)->get();

        $this->assertNotEmpty($students, 'fixture school must have students');

        foreach ($students as $student) {
            $current = $student->currentEnrollment;

            $this->assertNotNull($current, "student {$student->id} resolves a current enrollment");
            $this->assertNotNull($current->course_id);
            $this->assertNotNull($current->section_id);
            $this->assertContains($current->status, [
                Enrollment::STATUS_ACTIVE,
                Enrollment::STATUS_PROMOTED,
            ], "student {$student->id} has a valid current enrollment status");
        }
    }

    public function test_current_enrollment_accessor_returns_latest_row_across_academic_years()
    {
        $tag = uniqid('PTEA_', true);

        [$yearA, $courseA, $sectionA] = $this->makeYearCourseAndSection($tag.'YearA', $tag.' Level One');
        [$yearB, $courseB, $sectionB] = $this->makeYearCourseAndSection($tag.'YearB', $tag.' Level Two');

        $student = $this->makeStudent();

        $first = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearA->id,
            'course_id' => $courseA->id,
            'section_id' => $sectionA->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => $yearA->start_date,
            'reason' => 'initial_enrollment',
        ]);

        $second = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearB->id,
            'course_id' => $courseB->id,
            'section_id' => $sectionB->id,
            'status' => Enrollment::STATUS_PROMOTED,
            'effective_date' => $yearB->start_date,
            'reason' => 'promotion',
        ]);

        // The creating callback in Student::booted() accesses currentEnrollment
        // during save, which caches null before the enrollment exists. Reload to
        // get a clean relation cache.
        $student->unsetRelation('currentEnrollment');

        $current = $student->currentEnrollment;

        $this->assertNotNull($current);
        $this->assertSame($second->id, $current->id);
        $this->assertSame($courseB->id, $current->course_id);
        $this->assertSame($sectionB->id, $current->section_id);
        $this->assertNotSame($first->id, $current->id);

        $this->cleanupEnrollmentTest($student, [$yearA, $yearB], [$courseA, $courseB], [$sectionA, $sectionB]);
    }

    public function test_multiple_enrollment_rows_are_allowed_within_the_same_academic_year()
    {
        $tag = uniqid('PTMA_', true);

        [$year, $course, $section] = $this->makeYearCourseAndSection($tag.'Year', $tag.' Course One');
        [$yearB, $courseB, $sectionB] = $this->makeYearCourseAndSection($tag.'YearB', $tag.' Course Two');

        $student = $this->makeStudent();

        $original = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $course->id,
            'section_id' => $section->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => $year->start_date,
            'reason' => 'initial_enrollment',
        ]);

        // Second row for same student + same year = mid-year transfer.
        $transfer = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $courseB->id,
            'section_id' => $sectionB->id,
            'status' => Enrollment::STATUS_TRANSFERRED_OUT,
            'effective_date' => $year->start_date->copy()->addMonths(3),
            'reason' => 'manual_transfer',
        ]);

        $this->assertDatabaseHas('enrollments', ['id' => $transfer->id]);

        $student->unsetRelation('currentEnrollment');
        $current = $student->currentEnrollment;
        $this->assertSame($transfer->id, $current->id, 'latest row wins within the same year');

        // Append-only invariant: original row was not mutated.
        $this->assertSame($course->id, $original->fresh()->course_id);
        $this->assertSame($section->id, $original->fresh()->section_id);

        $this->cleanupEnrollmentTest($student, [$year, $yearB], [$course, $courseB], [$section, $sectionB]);
    }

    private function makeYearCourseAndSection(string $yearName, string $courseName): array
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => $yearName,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        $course = Course::create([
            'school_id' => $this->school->id,
            'name' => $courseName,
            'code' => strtoupper(substr(md5($courseName), 0, 6)),
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => 'A',
        ]);

        return [$year, $course, $section];
    }

    private function makeStudent(): Student
    {
        $suffix = substr(md5(uniqid('', true)), 0, 6);

        return Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'PromoTest',
            'last_name' => 'Fixture'.$suffix,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(12),
            'admission_date' => now(),
            'status' => 'active',
        ]);
    }

    private function cleanupEnrollmentTest(Student $student, array $years, array $courses, array $sections): void
    {
        Enrollment::withoutGlobalScopes()->where('student_id', $student->id)->delete();
        $student->forceDelete();

        foreach ($years as $year) {
            AcademicYear::withoutGlobalScopes()->where('id', $year->id)->delete();
        }
        foreach ($courses as $course) {
            Course::withoutGlobalScopes()->where('id', $course->id)->delete();
        }
        foreach ($sections as $section) {
            Section::withoutGlobalScopes()->where('id', $section->id)->delete();
        }
    }
}
