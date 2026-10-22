<?php

namespace Tests\Feature;

use App\Livewire\TopbarCommandCenter;
use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * An overdue task is worth telling the assignee about once. It is not worth
 * telling them about every five minutes, and the notice must point at the
 * reader's own workspace — never at the platform.
 */
class TaskOverdueNotificationTest extends TestCase
{
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

    public function test_an_overdue_task_is_announced_once_and_never_repeated(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            Notification::fake();

            // The sweeper runs every five minutes. Run it three times.
            $this->artisan('schedule:send-reminders')->assertExitCode(0);
            $this->artisan('schedule:send-reminders')->assertExitCode(0);
            $this->artisan('schedule:send-reminders')->assertExitCode(0);

            Notification::assertSentToTimes($assignee, TaskOverdueNotification::class, 1);

            $this->assertNotNull($task->fresh()->overdue_notified_at);
        } finally {
            DB::rollBack();
        }
    }

    public function test_a_read_overdue_notice_is_never_announced_again(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            $this->artisan('schedule:send-reminders')->assertExitCode(0);

            $notification = $assignee->notifications()->first();
            $this->assertNotNull($notification);
            $notification->markAsRead();

            // Even if the marker on the task is lost (a task that predates the
            // column, a task that was edited), a notice the reader already has
            // is not sent again.
            $task->forceFill(['overdue_notified_at' => null])->save();

            $this->artisan('schedule:send-reminders')->assertExitCode(0);

            $this->assertSame(
                1,
                $assignee->notifications()->where('type', TaskOverdueNotification::class)->count(),
                'The overdue notice was announced again after it had been read.'
            );
        } finally {
            DB::rollBack();
        }
    }

    public function test_a_task_that_is_reopened_may_be_announced_again(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            Notification::fake();

            $this->artisan('schedule:send-reminders')->assertExitCode(0);
            Notification::assertSentToTimes($assignee, TaskOverdueNotification::class, 1);

            // The task is done and then opened again: a fresh overdue period.
            $task->markDone();
            $task->markOpen();

            $this->assertNull($task->fresh()->overdue_notified_at);

            $this->artisan('schedule:send-reminders')->assertExitCode(0);

            Notification::assertSentToTimes($assignee, TaskOverdueNotification::class, 2);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_notice_points_at_the_readers_own_workspace(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            $assignee->notify(new TaskOverdueNotification($task));
            $notification = $assignee->notifications()->latest('created_at')->first();

            $this->assertNotNull($notification);

            $this->actingAs($assignee);

            $url = Livewire::test(TopbarCommandCenter::class)
                ->instance()
                ->notificationUrl($notification);

            $this->assertNotNull($url);
            $this->assertStringContainsString('/workspace/my-day', $url);
            $this->assertStringNotContainsString('/platform', $url);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * A notice written before the links were resolved carries an absolute URL
     * that points at the platform. It must not be handed to a tenant either.
     */
    public function test_an_old_notice_with_a_platform_link_is_repointed_at_the_tenant(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            $assignee->notify(new TaskOverdueNotification($task));
            $notification = $assignee->notifications()->latest('created_at')->first();

            $this->assertNotNull($notification);

            // Rewrite the stored link the way the scheduler used to.
            $data = $notification->data;
            $data['url'] = 'https://kairo.co.zw/platform/my-day';
            $notification->data = $data;
            $notification->save();

            $this->actingAs($assignee);

            $url = Livewire::test(TopbarCommandCenter::class)
                ->instance()
                ->notificationUrl($notification);

            $this->assertNotNull($url);
            $this->assertStringNotContainsString('/platform', $url);
            $this->assertStringContainsString('/workspace/my-day', $url);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * A tenant must never be handed a link to another host — least of all the
     * platform panel.
     */
    public function test_a_link_to_another_host_is_refused(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');

            $this->actingAs($assignee);

            $component = Livewire::test(TopbarCommandCenter::class)->instance();

            $method = new \ReflectionMethod($component, 'isOwnHostUrl');
            $method->setAccessible(true);

            $this->assertFalse($method->invoke($component, 'https://kairo.co.zw/platform/my-day'));
            $this->assertFalse($method->invoke($component, 'https://another-school.kairo.co.zw/workspace'));
            $this->assertTrue($method->invoke($component, request()->getHost() === '' ? '/workspace' : 'http://'.request()->getHost().'/workspace/my-day'));
            $this->assertFalse($method->invoke($component, null));
        } finally {
            DB::rollBack();
        }
    }

    public function test_opening_a_notice_marks_it_as_read(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $assignee = $this->makeUser($school, 'teaching_staff');
            $task = $this->makeTask($school, $assignee, now()->subDay());

            $assignee->notify(new TaskOverdueNotification($task));
            $notification = $assignee->notifications()->latest('created_at')->first();

            $this->assertNotNull($notification);

            $this->actingAs($assignee);

            Livewire::test(TopbarCommandCenter::class)
                ->call('markNotificationRead', $notification->id);

            $this->assertNotNull($notification->fresh()->read_at);
        } finally {
            DB::rollBack();
        }
    }

    private function makeSchool(array $attributes = []): School
    {
        return School::create(array_merge([
            'name' => 'Overdue School '.substr(uniqid(), -6),
            'subdomain' => 'overdue-'.substr(uniqid(), -8),
            'status' => 'active',
        ], $attributes));
    }

    private function makeUser(School $school, string $role): User
    {
        $suffix = substr(uniqid(), -8);

        return User::create([
            'school_id' => $school->id,
            'name' => 'Overdue '.$role.' '.$suffix,
            'email' => 'overdue-'.$suffix.'@example.test',
            'username' => 'overdue-'.$suffix,
            'password' => 'Password@12345',
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => $role,
        ]);
    }

    private function makeTask(School $school, User $assignee, $dueDate): UserTask
    {
        return UserTask::create([
            'school_id' => $school->id,
            'created_by_id' => $assignee->id,
            'assigned_to_id' => $assignee->id,
            'title' => 'Test the changes',
            'due_date' => $dueDate,
            'status' => UserTask::STATUS_OPEN,
        ]);
    }
}
