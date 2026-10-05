<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

/**
 * The student's Current Enrollment block must show the enrollment that actually
 * exists.
 *
 * It used to read course.name, section.name and academicYear.name straight off
 * the student. Those columns live on the enrollment, not on students, so the
 * block reported "Not assigned" for every student - including the ones the Edit
 * form, right next to it, correctly showed as Grade 4 / North / 2027.
 */
class StudentCurrentEnrollmentDisplayTest extends TestCase
{
    private const FIXTURE_SUBDOMAIN = 'enrollmentdisplayfixture';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    protected function tearDown(): void
    {
        School::where('subdomain', self::FIXTURE_SUBDOMAIN)->get()->each(function (School $school): void {
            Enrollment::where('school_id', $school->id)->delete();
            Student::where('school_id', $school->id)->forceDelete();
            Section::where('school_id', $school->id)->delete();
            Course::where('school_id', $school->id)->delete();
            AcademicYear::where('school_id', $school->id)->delete();
        });

        parent::tearDown();
    }

    /**
     * A school with one enrolled student and one student with no enrollment at
     * all, already signed in as its administrator.
     *
     * @return array{school: School, enrolled: Student, unenrolled: Student}
     */
    private function schoolWithOneEnrolledStudent(): array
    {
        $this->purgeFixtureRows();

        $school = School::where('subdomain', self::FIXTURE_SUBDOMAIN)->first()
            ?? School::create([
                'name' => 'Enrollment Display Fixture',
                'subdomain' => self::FIXTURE_SUBDOMAIN,
                'status' => 'active',
            ]);

        $user = User::where('school_id', $school->id)
            ->where('email', 'admin@'.self::FIXTURE_SUBDOMAIN.'.test')
            ->first();

        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Enrollment Admin',
                'email' => 'admin@'.self::FIXTURE_SUBDOMAIN.'.test',
                'password' => bcrypt('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);

            PermissionRegistry::ensureAdminHasRole($user, $school->id);
        }

        $roleId = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->value('id');
        $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();

        App::instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->actingAs($user);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->startOfYear()->addYear()->toDateString(),
            'is_active' => true,
        ]);

        $course = Course::create(['school_id' => $school->id, 'name' => 'Grade 4']);
        $section = Section::create([
            'school_id' => $school->id,
            'course_id' => $course->id,
            'name' => 'North',
        ]);

        $enrolled = $this->student($school, 'Tatenda');
        $unenrolled = $this->student($school, 'Tadiwa');

        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $enrolled->id,
            'academic_year_id' => $year->id,
            'course_id' => $course->id,
            'section_id' => $section->id,
            'roll_number' => '12',
        ]);

        return ['school' => $school, 'enrolled' => $enrolled, 'unenrolled' => $unenrolled];
    }

    private function purgeFixtureRows(): void
    {
        School::where('subdomain', self::FIXTURE_SUBDOMAIN)->get()->each(function (School $school): void {
            Enrollment::where('school_id', $school->id)->delete();
            Student::where('school_id', $school->id)->forceDelete();
            Section::where('school_id', $school->id)->delete();
            Course::where('school_id', $school->id)->delete();
            AcademicYear::where('school_id', $school->id)->delete();
        });
    }

    private function student(School $school, string $firstName): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'first_name' => $firstName,
            'last_name' => 'Sithole',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'admission_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function pageText(School $school, string $path): string
    {
        $response = $this->get('http://'.$school->subdomain.'.lvh.me'.$path);

        $response->assertOk();

        return html_entity_decode(strip_tags((string) $response->getContent()));
    }

    public function test_the_view_page_shows_the_student_current_enrollment(): void
    {
        ['school' => $school, 'enrolled' => $student] = $this->schoolWithOneEnrolledStudent();

        $text = $this->pageText($school, '/workspace/students/'.$student->id);

        $this->assertStringContainsString('Current Enrollment', $text);
        $this->assertStringContainsString('2027', $text, 'The academic year must be shown on the view page.');
        $this->assertStringContainsString('Grade 4', $text, 'The form/grade level must be shown on the view page.');
        $this->assertStringContainsString('North', $text, 'The stream/class must be shown on the view page.');
        $this->assertStringNotContainsString(
            'Not assigned',
            $text,
            'An enrolled student must not be reported as not assigned.'
        );
    }

    public function test_the_view_page_agrees_with_the_edit_page(): void
    {
        ['school' => $school, 'enrolled' => $student] = $this->schoolWithOneEnrolledStudent();

        $view = $this->pageText($school, '/workspace/students/'.$student->id);
        $edit = $this->pageText($school, '/workspace/students/'.$student->id.'/edit');

        foreach (['2027', 'Grade 4', 'North'] as $expected) {
            $this->assertStringContainsString($expected, $view, "The view page is missing [{$expected}].");
            $this->assertStringContainsString($expected, $edit, "The edit page is missing [{$expected}].");
        }
    }

    public function test_a_student_with_no_enrollment_is_reported_as_not_assigned(): void
    {
        ['school' => $school, 'unenrolled' => $student] = $this->schoolWithOneEnrolledStudent();

        $text = $this->pageText($school, '/workspace/students/'.$student->id);

        $this->assertStringContainsString('Not assigned', $text, 'A student with no enrollment must say so.');
    }
}
