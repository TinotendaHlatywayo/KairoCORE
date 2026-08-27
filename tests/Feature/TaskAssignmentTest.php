<?php

namespace Tests\Feature;

use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\Schedule;
use App\Filament\Student\Pages\StudentSchedule;
use App\Livewire\StudentTopbarCommandCenter;
use App\Livewire\TopbarCommandCenter;
use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Communication\Models\EventCalendar;
use Modules\Students\Models\Student;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
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

    protected function admin(): User
    {
        $school = School::find(15);
        App::instance('current_tenant', $school);
        view()->share('school', $school);
        $user = User::find(13);
        $user->forceFill(['account_status' => 'active'])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_myday_renders_new_assignee_picker(): void
    {
        $this->admin();

        $html = Livewire::test(MyDay::class)
            ->call('openTaskModal', now()->toDateString())
            ->html();

        $this->assertStringContainsString('tapPicker', $html);
        $this->assertStringContainsString('sc-tap-tabs', $html);
        $this->assertStringContainsString('mode ===', $html);
    }

    public function test_assign_to_self_creates_single_task(): void
    {
        $user = $this->admin();

        Livewire::test(MyDay::class)
            ->call('openTaskModal', now()->toDateString())
            ->set('taskForm.title', 'Self task')
            ->set('taskForm.assignee_spec', json_encode(['mode' => 'self']))
            ->call('saveTask');

        $this->assertDatabaseHas('user_tasks', [
            'title' => 'Self task',
            'created_by_id' => $user->id,
            'assigned_to_id' => $user->id,
        ]);
    }

    public function test_assign_to_specific_staff_fans_out(): void
    {
        $user = $this->admin();
        $colleague = User::find(18);

        Livewire::test(MyDay::class)
            ->call('openTaskModal', now()->toDateString())
            ->set('taskForm.title', 'Staff task')
            ->set('taskForm.assignee_spec', json_encode(['mode' => 'staff', 'staff_ids' => [$colleague->id]]))
            ->call('saveTask');

        $this->assertDatabaseHas('user_tasks', [
            'title' => 'Staff task',
            'created_by_id' => $user->id,
            'assigned_to_id' => $colleague->id,
        ]);
    }

    public function test_assign_to_everyone_in_role_fans_out_to_members(): void
    {
        $user = $this->admin();
        $member = User::where('school_id', 15)
            ->where('custom_role_id', 2)
            ->where('account_status', 'active')
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($member);

        Livewire::test(MyDay::class)
            ->call('openTaskModal', now()->toDateString())
            ->set('taskForm.title', 'Role task')
            ->set('taskForm.assignee_spec', json_encode(['mode' => 'role', 'role_id' => 2, 'all' => true]))
            ->call('saveTask');

        foreach ($member as $id) {
            $this->assertDatabaseHas('user_tasks', [
                'title' => 'Role task',
                'assigned_to_id' => $id,
            ]);
        }
    }

    public function test_assign_to_whole_school_students_fans_out(): void
    {
        $user = $this->admin();

        $studentUserIds = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->filter()
            ->all();

        $this->assertNotEmpty($studentUserIds);

        Livewire::test(MyDay::class)
            ->call('openTaskModal', now()->toDateString())
            ->set('taskForm.title', 'School students task')
            ->set('taskForm.assignee_spec', json_encode(['mode' => 'students', 'scope' => 'school']))
            ->call('saveTask');

        foreach ($studentUserIds as $uid) {
            $this->assertDatabaseHas('user_tasks', [
                'title' => 'School students task',
                'assigned_to_id' => $uid,
            ]);
        }
    }

    public function test_assign_to_class_fans_out_to_section_members(): void
    {
        $user = $this->admin();

        // Self-sufficient fixture: the dev database cannot be relied on to
        // always contain a user-linked student enrolled in section 3.
        $sectionId = DB::table('sections')->where('school_id', 15)->orderBy('id')->value('id');
        $this->assertNotNull($sectionId, 'School 15 must have at least one section');

        $suffix = uniqid();
        $linkedUser = User::create([
            'school_id' => 15,
            'name' => 'Class Fixture Student',
            'email' => "class.fixture.{$suffix}@demo.schoolcore.test",
            'password' => bcrypt(\Illuminate\Support\Str::random(64)),
            'account_status' => 'active',
            'requested_role' => 'student',
        ]);

        $student = Student::create([
            'school_id' => 15,
            'user_id' => $linkedUser->id,
            'student_id_number' => 'TEST-TASK-'.strtoupper($suffix),
            'admission_number' => 'TEST-TADM-'.strtoupper($suffix),
            'first_name' => 'Class',
            'last_name' => 'Fixture',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->startOfYear()->toDateString(),
            'status' => 'active',
        ]);

        $year = \Modules\Academics\Models\AcademicYear::withoutGlobalScopes()->where('school_id', 15)->orderBy('id')->first()
            ?? \Modules\Academics\Models\AcademicYear::create([
                'school_id' => 15, 'name' => now()->format('Y').' Academic Year', 'is_active' => true,
                'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
            ]);

        $enrollment = \Modules\Students\Models\Enrollment::create([
            'school_id' => 15,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'course_id' => DB::table('sections')->where('id', $sectionId)->value('course_id'),
            'section_id' => $sectionId,
        ]);

        try {
            Livewire::test(MyDay::class)
                ->call('openTaskModal', now()->toDateString())
                ->set('taskForm.title', 'Class task')
                ->set('taskForm.assignee_spec', json_encode(['mode' => 'students', 'scope' => 'class', 'section_id' => $sectionId]))
                ->call('saveTask');

            $this->assertDatabaseHas('user_tasks', [
                'title' => 'Class task',
                'assigned_to_id' => $linkedUser->id,
            ]);
        } finally {
            // Remove the fixture so repeated runs stay clean.
            \Modules\Students\Models\Enrollment::withoutGlobalScopes()->whereKey($enrollment->id)->delete();
            \App\Models\UserTask::where('assigned_to_id', $linkedUser->id)->delete();
            $student->forceDelete();
            $linkedUser->forceDelete();
        }
    }

    public function test_edit_task_shows_single_assignee_spec(): void
    {
        $this->admin();
        $colleague = User::find(18);

        $task = UserTask::create([
            'school_id' => 15,
            'created_by_id' => 13,
            'assigned_to_id' => $colleague->id,
            'title' => 'Editable',
            'status' => UserTask::STATUS_OPEN,
        ]);

        Livewire::test(MyDay::class)
            ->call('editTask', $task->id)
            ->assertSet('taskForm.assignee_spec', json_encode([
                'mode' => 'staff',
                'staff_ids' => [(int) $colleague->id],
            ]));
    }

    public function test_student_schedule_mirrors_staff_ui_without_assignee_picker(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->assertFalse(PermissionRegistry::userCan($student->user, 'tasks.assign'));

        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $html = Livewire::test(StudentSchedule::class)
            ->html();

        // Mirrors the staff schedule UI…
        $this->assertStringContainsString('sc-sched-toolbar', $html);
        $this->assertStringContainsString('sc-sched-views', $html);
        $this->assertStringContainsString('Month', $html);
        $this->assertStringContainsString('sc-picker', $html);
        $this->assertStringContainsString('sc-month-grid', $html);

        // …but the assignee picker is hidden for students.
        $this->assertStringNotContainsString('tapPicker', $html);
        $this->assertStringNotContainsString('task-assignee-picker', $html);
    }

    public function test_student_cannot_assign_task_to_others(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        Livewire::test(StudentSchedule::class)
            ->call('openTaskModal', now()->toDateString())
            ->set('taskForm.title', 'Student self task')
            ->set('taskForm.assignee_spec', json_encode(['mode' => 'staff', 'staff_ids' => [18]]))
            ->call('saveTask');

        // Fan-out is clamped to the student themselves.
        $this->assertDatabaseHas('user_tasks', [
            'title' => 'Student self task',
            'created_by_id' => $student->user_id,
            'assigned_to_id' => $student->user_id,
        ]);

        $this->assertDatabaseMissing('user_tasks', [
            'title' => 'Student self task',
            'created_by_id' => $student->user_id,
            'assigned_to_id' => 18,
        ]);
    }

    public function test_student_event_creation_targets_students(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        Livewire::test(StudentSchedule::class)
            ->call('openEventModal', now()->toDateString())
            ->assertSet('eventForm.category', 'academic')
            ->set('eventForm.title', 'Student study group')
            ->call('saveEvent');

        $this->assertDatabaseHas('communication_events', [
            'title' => 'Student study group',
            'created_by_id' => $student->user_id,
            'category' => 'academic',
        ]);

        $event = EventCalendar::query()
            ->where('title', 'Student study group')
            ->where('created_by_id', $student->user_id)
            ->first();
        $this->assertNotNull($event);
        $this->assertSame(['student'], $event->target_roles);
    }

    public function test_student_topbar_command_center_hides_assignee_picker(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $html = Livewire::test(StudentTopbarCommandCenter::class)
            ->html();

        // Date & time trigger + Task Manager present…
        $this->assertStringContainsString('sc-cc-trigger', $html);
        $this->assertStringContainsString('sc-cc-pane-tasks', $html);
        $this->assertStringContainsString('sc-cc-pane-cal', $html);

        // …links to the student schedule…
        $this->assertStringContainsString('my-schedule', $html);

        // …and no assignee picker for students, nor the permission-gated
        // "Clear All" action.
        $this->assertStringNotContainsString('assignee', $html);
        $this->assertStringNotContainsString('Clear All', $html);
    }

    public function test_command_center_links_point_at_schedule_with_deep_link_params(): void
    {
        $this->admin();

        $task = UserTask::create([
            'school_id' => 15,
            'created_by_id' => 13,
            'assigned_to_id' => 13,
            'title' => 'Deep link task',
            'status' => UserTask::STATUS_OPEN,
            'due_date' => now()->toDateString(),
        ]);

        $html = Livewire::test(TopbarCommandCenter::class)
            ->call('openAddTask', $task->due_date->toDateString())
            ->html();

        // Task list rows deep-link to the schedule with the task id.
        $this->assertStringContainsString('?task='.$task->id, $html);
        $this->assertStringContainsString('Open in Schedule', $html);

        // Agenda rows deep-link to the schedule with the task id.
        $scheduleUrl = Schedule::getUrl();
        $this->assertStringContainsString('sc-cc-agenda-row', $html);
        $this->assertStringContainsString('href="'.$scheduleUrl.'?task='.$task->id.'"', $html);
    }

    public function test_schedule_deep_link_task_opens_task_modal(): void
    {
        $this->admin();

        $task = UserTask::create([
            'school_id' => 15,
            'created_by_id' => 13,
            'assigned_to_id' => 13,
            'title' => 'Deep link task',
            'status' => UserTask::STATUS_OPEN,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->get(Schedule::getUrl().'?task='.$task->id);
        $response->assertStatus(200);
        $this->assertStringContainsString('sc-modal-title">Edit Task', $response->getContent());
        $this->assertStringContainsString('Deep link task', $response->getContent());
    }

    public function test_schedule_deep_link_event_opens_event_modal(): void
    {
        $this->admin();

        $event = EventCalendar::create([
            'school_id' => 15,
            'created_by_id' => 13,
            'organizer_id' => 13,
            'title' => 'Deep link event',
            'category' => 'general',
            'start_time' => now()->startOfDay()->addHours(9),
            'end_time' => now()->startOfDay()->addHours(10),
            'color' => '#4f46e5',
        ]);

        $response = $this->get(Schedule::getUrl().'?event='.$event->id);
        $response->assertStatus(200);
        $this->assertStringContainsString('sc-modal-title">Edit Event', $response->getContent());
        $this->assertStringContainsString('Deep link event', $response->getContent());
    }

    public function test_student_deep_link_to_task_opens_task_modal(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $task = UserTask::create([
            'school_id' => 15,
            'created_by_id' => $student->user_id,
            'assigned_to_id' => $student->user_id,
            'title' => 'Student deep link task',
            'status' => UserTask::STATUS_OPEN,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->get(StudentSchedule::getUrl().'?task='.$task->id);
        $response->assertStatus(200);
        $this->assertStringContainsString('sc-modal-title">Edit Task', $response->getContent());
        $this->assertStringContainsString('Student deep link task', $response->getContent());
    }

    public function test_student_cannot_open_others_event_via_deep_link(): void
    {
        $this->admin();
        $student = Student::where('school_id', 15)
            ->whereNotNull('user_id')
            ->with('user')
            ->first();
        $this->assertNotNull($student);
        $this->actingAs($student->user);
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $event = EventCalendar::create([
            'school_id' => 15,
            'created_by_id' => 13,
            'organizer_id' => 13,
            'title' => 'Admin only event',
            'category' => 'general',
            'start_time' => now()->startOfDay()->addHours(9),
            'end_time' => now()->startOfDay()->addHours(10),
            'color' => '#4f46e5',
        ]);

        $response = $this->get(StudentSchedule::getUrl().'?event='.$event->id);
        $response->assertStatus(200);
        // The event modal must NOT open for a school event the student didn't create.
        $this->assertStringNotContainsString('sc-modal-title">Edit Event', $response->getContent());
    }
}
