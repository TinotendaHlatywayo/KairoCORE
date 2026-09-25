<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ReportEnrollmentResolutionTest extends TestCase
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

    public function test_report_for_source_year_resolves_promoted_students_current_class()
    {
        $tag = uniqid('PRPT_', true);

        [$sourceYear, $courseOld, $sectionOld] = $this->makeYearCourseAndSection($tag.'Source', $tag.' Grade 3');
        [$targetYear, $courseNew, $sectionNew] = $this->makeYearCourseAndSection($tag.'Target', $tag.' Grade 4');

        $student = $this->makeStudent();

        // Promotion commit archives the source-year row and creates a new
        // active row in the TARGET academic year.
        $old = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $sourceYear->id,
            'course_id' => $courseOld->id,
            'section_id' => $sectionOld->id,
            'status' => Enrollment::STATUS_PROMOTED,
            'effective_date' => now(),
            'reason' => 'promotion',
        ]);

        $current = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $targetYear->id,
            'course_id' => $courseNew->id,
            'section_id' => $sectionNew->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => now(),
            'reason' => 'promotion',
        ]);

        // Report generated for the source-year term, bound to the pre-promotion section.
        $term = $this->makeTerm($sourceYear, $tag.'Term');
        $report = $this->makeReport($student, $term, $sectionOld);

        // The creating callback in Student::booted() caches currentEnrollment as
        // null before the enrollments exist; reload the relation.
        $student->unsetRelation('currentEnrollment');
        $dire = $student->currentEnrollment;

        $this->assertSame($current->id, $dire->id, 'directory current enrollment is the promoted placement');
        $this->assertSame($older = $old->id, $older, 'sanity: source enrollment id captured');
        $this->assertNotSame($current->id, $old->id);

        $resolved = $report->resolveTermEnrollment();

        $this->assertNotNull($resolved);
        $this->assertSame($current->id, $resolved->id, 'report resolves the current (promoted) enrollment');
        $this->assertSame($sectionNew->id, $resolved->section_id);
        $this->assertSame($sectionNew->id, $report->resolveTermSection()?->id, 'report class matches the directory placement');

        $this->cleanupPromotionTest($student, $report, $term, [$sourceYear, $targetYear], [$courseOld, $courseNew], [$sectionOld, $sectionNew]);
    }

    public function test_report_resolves_active_enrollment_within_the_same_academic_year()
    {
        $tag = uniqid('PRSW_', true);

        [$year, $course, $section] = $this->makeYearCourseAndSection($tag.'Year', $tag.' Course One');
        [$courseB, $sectionB] = $this->makeCourseAndSection($tag.' Course Two');

        $student = $this->makeStudent();

        // Mid-year stream change: old row archived, new active row in the SAME year.
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $course->id,
            'section_id' => $section->id,
            'status' => Enrollment::STATUS_TRANSFERRED_OUT,
            'effective_date' => now(),
            'reason' => 'manual_transfer',
        ]);

        $active = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $courseB->id,
            'section_id' => $sectionB->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => now(),
            'reason' => 'manual_transfer',
        ]);

        $term = $this->makeTerm($year, $tag.'Term');
        $report = $this->makeReport($student, $term, $section);

        $this->assertSame($sectionB->id, $report->resolveTermSection()?->id, 'same-year move resolves the live stream');
        $this->assertSame($active->id, $report->resolveTermEnrollment()?->id);

        $this->cleanupPromotionTest($student, $report, $term, [$year], [$course, $courseB], [$section, $sectionB]);
    }

    public function test_report_resolves_normal_active_enrollment_for_its_own_year()
    {
        $tag = uniqid('PRNM_', true);

        [$year, $course, $section] = $this->makeYearCourseAndSection($tag.'Year', $tag.' Course');

        $student = $this->makeStudent();

        $active = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => $course->id,
            'section_id' => $section->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'effective_date' => now(),
            'reason' => 'initial_enrollment',
        ]);

        $term = $this->makeTerm($year, $tag.'Term');
        $report = $this->makeReport($student, $term, $section);

        $this->assertSame($active->id, $report->resolveTermEnrollment()?->id);
        $this->assertSame($section->id, $report->resolveTermSection()?->id);

        $this->cleanupPromotionTest($student, $report, $term, [$year], [$course], [$section]);
    }

    private function makeTerm(AcademicYear $year, string $name): Term
    {
        return Term::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => false,
        ]);
    }

    private function makeReport(Student $student, Term $term, Section $section): AcademicReport
    {
        return AcademicReport::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'section_id' => $section->id,
            'term_id' => $term->id,
            'status' => 'draft',
            'unhu_competencies' => [],
        ]);
    }

    private function makeYearCourseAndSection(string $yearName, string $courseName): array
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => $yearName,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        [$course, $section] = $this->makeCourseAndSection($courseName);

        return [$year, $course, $section];
    }

    private function makeCourseAndSection(string $courseName): array
    {
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

        return [$course, $section];
    }

    private function makeStudent(): Student
    {
        $suffix = substr(md5(uniqid('', true)), 0, 6);

        return Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'ReportFix',
            'last_name' => 'Fixture'.$suffix,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(12),
            'admission_date' => now(),
            'status' => 'active',
        ]);
    }

    private function cleanupPromotionTest(Student $student, AcademicReport $report, Term $term, array $years, array $courses, array $sections): void
    {
        AcademicReport::withoutGlobalScopes()->where('id', $report->id)->delete();
        Enrollment::withoutGlobalScopes()->where('student_id', $student->id)->delete();
        $student->forceDelete();

        Term::withoutGlobalScopes()->where('id', $term->id)->delete();

        foreach ($courses as $course) {
            Course::withoutGlobalScopes()->where('id', $course->id)->delete();
        }
        foreach ($sections as $section) {
            Section::withoutGlobalScopes()->where('id', $section->id)->delete();
        }
        foreach ($years as $year) {
            AcademicYear::withoutGlobalScopes()->where('id', $year->id)->delete();
        }
    }
}