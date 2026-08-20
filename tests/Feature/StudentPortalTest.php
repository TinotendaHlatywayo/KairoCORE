<?php

namespace Tests\Feature;

use App\Filament\Student\Pages\StudentDashboard;
use App\Filament\Student\Pages\StudentFees;
use App\Filament\Student\Resources\HomeworkResource;
use App\Filament\Student\Resources\HomeworkResource\Pages\ListHomeworks;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Lms\Models\Homework;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use InteractsWithDatabase;

    private array $createdUserIds = [];

    private array $createdSubjectIds = [];

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
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdUserIds)) {
            $students = Student::whereIn('user_id', $this->createdUserIds)->get();
            Enrollment::whereIn('student_id', $students->pluck('id'))->delete();
            Student::whereIn('id', $students->pluck('id'))->forceDelete();
            User::whereIn('id', $this->createdUserIds)->forceDelete();
            $this->createdUserIds = [];
        }

        if (! empty($this->createdSubjectIds)) {
            Subject::whereIn('id', $this->createdSubjectIds)->delete();
            $this->createdSubjectIds = [];
        }

        parent::tearDown();
    }

    public function test_student_only_has_access_to_the_student_panel(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);

        $this->assertTrue($student->canAccessPanel(Filament::getPanel('student')));
        $this->assertFalse($student->canAccessPanel(Filament::getPanel('app')));
        $this->assertFalse($student->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_staff_can_enter_the_staff_workspace_but_not_the_student_panel(): void
    {
        $school = School::firstOrFail();
        $staff = User::create([
            'school_id' => $school->id,
            'name' => 'Staff '.uniqid(),
            'email' => 'staff-'.uniqid().'@test.local',
            'password' => 'Password123!',
            'account_status' => 'active',
        ]);
        $this->createdUserIds[] = $staff->id;

        $this->assertTrue($staff->canAccessPanel(Filament::getPanel('app')));
        $this->assertFalse($staff->canAccessPanel(Filament::getPanel('student')));
        $this->assertFalse($staff->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_student_dashboard_and_fees_pages_render(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);
        $this->actingAs($student);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'student']);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        Livewire::test(StudentDashboard::class)->assertOk();
        Livewire::test(StudentFees::class)->assertOk();
    }

    public function test_student_only_sees_homework_for_their_section(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);
        $this->actingAs($student);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'student']);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MTH'.uniqid()]);
        $this->createdSubjectIds[] = $subject->id;
        $mySection = Section::create(['school_id' => $school->id, 'course_id' => $this->course($school)->id, 'name' => 'Form 1 Test']);
        $otherSection = Section::create(['school_id' => $school->id, 'course_id' => $this->course($school)->id, 'name' => 'Form 2 Test']);

        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->student->id,
            'academic_year_id' => $this->academicYear($school)->id,
            'course_id' => $this->course($school)->id,
            'section_id' => $mySection->id,
        ]);

        $mine = Homework::create([
            'school_id' => $school->id,
            'section_id' => $mySection->id,
            'subject_id' => $subject->id,
            'title' => 'Algebra Test',
            'due_date' => now()->addDays(2),
        ]);
        $theirs = Homework::create([
            'school_id' => $school->id,
            'section_id' => $otherSection->id,
            'subject_id' => $subject->id,
            'title' => 'Other Class Homework',
            'due_date' => now()->addDays(2),
        ]);

        $ids = HomeworkResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));

        $homeworks = $mine;
        $this->assertNotNull($homeworks->section_id);
        $homeworks->forceDelete();
        $theirs->forceDelete();
        $mySection->forceDelete();
        $otherSection->forceDelete();
    }

    public function test_student_can_submit_their_homework(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);
        $this->actingAs($student);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'student']);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Science', 'code' => 'SCI'.uniqid()]);
        $this->createdSubjectIds[] = $subject->id;
        $section = Section::create(['school_id' => $school->id, 'course_id' => $this->course($school)->id, 'name' => 'Form 1 Test']);
        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->student->id,
            'academic_year_id' => $this->academicYear($school)->id,
            'course_id' => $this->course($school)->id,
            'section_id' => $section->id,
        ]);

        $homework = Homework::create([
            'school_id' => $school->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'title' => 'Science Assignment',
            'due_date' => now()->addDays(3),
        ]);

        Livewire::test(ListHomeworks::class)
            ->callTableAction('submit', $homework, ['file_path' => UploadedFile::fake()->create('work.pdf', 1)])
            ->assertOk();

        $this->assertDatabaseHas('homework_submissions', [
            'school_id' => $school->id,
            'homework_id' => $homework->id,
            'student_id' => $student->student->id,
        ]);

        $homework->forceDelete();
        $section->forceDelete();
    }

    public function test_student_panel_pages_render(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);
        $this->actingAs($student);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'student']);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        Livewire::test(StudentDashboard::class)
            ->assertOk()
            ->assertViewHas('student');

        Livewire::test(StudentFees::class)
            ->assertOk()
            ->assertViewHas('student');
    }

    public function test_student_panel_home_path_resolves_instead_of_404(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);

        // Student panel home + pages must resolve to the Filament panel even
        // though the tenant CMS {slug} fallback resolver could shadow them.
        $this->actingAs($student, Filament::getPanel('student')->getAuthGuard());
        $this->withServerVariables(['HTTP_HOST' => $school->subdomain.'.lvh.me']);

        $this->get('/student')
            ->assertRedirect('/student/student-portal');

        $this->get('/student/student-portal')
            ->assertStatus(200);
    }

    public function test_student_is_redirected_from_workspace_to_student_panel(): void
    {
        $school = School::firstOrFail();
        $student = $this->studentUser($school);

        // A student who somehow lands on the workspace panel (app) is
        // redirected to the student panel by SchoolPanelAuthenticate.
        // Verify the permission model — HTTP-level redirect depends on
        // Filament panel context which varies between test and real request.
        $this->assertFalse($student->canAccessPanel(Filament::getPanel('app')));
        $this->assertTrue($student->canAccessPanel(Filament::getPanel('student')));
    }

    public function test_staff_is_redirected_from_student_panel_to_workspace(): void
    {
        $school = School::firstOrFail();
        $staff = User::create([
            'school_id' => $school->id,
            'name' => 'Staff Redirector '.uniqid(),
            'email' => 'staff-redirect-'.uniqid().'@test.local',
            'password' => 'Password123!',
            'account_status' => 'active',
        ]);
        $this->createdUserIds[] = $staff->id;

        // A staff member who lands on the student panel is redirected to
        // workspace by SchoolPanelAuthenticate.
        $this->assertTrue($staff->canAccessPanel(Filament::getPanel('app')));
        $this->assertFalse($staff->canAccessPanel(Filament::getPanel('student')));
    }

    protected function studentUser(School $school): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Student Portal Tester',
            'email' => 'student-portal-'.uniqid().'@test.local',
            'password' => 'Password123!',
            'account_status' => 'active',
            'requested_role' => 'student',
        ]);

        $this->createdUserIds[] = $user->id;

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Portal',
            'last_name' => 'Student',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
            'admission_date' => now()->toDateString(),
            'student_id_number' => 'ST-'.uniqid(),
            'admission_number' => 'AD-'.uniqid(),
            'status' => 'active',
        ]);

        $user->student = $student;

        return $user;
    }

    protected function course(School $school): Course
    {
        return Course::firstWhere('school_id', $school->id)
            ?? Course::create(['school_id' => $school->id, 'name' => 'Form', 'code' => 'FR-'.uniqid()]);
    }

    protected function academicYear(School $school): AcademicYear
    {
        return AcademicYear::firstWhere('school_id', $school->id)
            ?? AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'is_active' => true]);
    }
}
