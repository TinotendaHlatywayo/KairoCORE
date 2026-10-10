<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Notifications\PlatformMessageNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Communication\Models\ChatMessage;
use Modules\Communication\Models\ChatThread;
use Modules\Communication\Services\PlatformChatBridge;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Models\PlatformMessageRecipient;
use Modules\SaaS\Services\PlatformMessagingService;
use Tests\TestCase;

class PlatformChatBridgeTest extends TestCase
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

    private function platformAdmin(): User
    {
        return User::whereNull('school_id')->firstOrFail();
    }

    private function studentUser(): User
    {
        $school = School::query()->firstOrFail();

        return User::query()
            ->where('school_id', $school->id)
            ->firstOrFail()
            ->forceFill(['requested_role' => 'student', 'account_status' => 'active'])
            ->save()
            ? User::query()->where('school_id', $school->id)->firstOrFail()
            : abort(500);
    }

    public function test_platform_message_to_a_user_is_mirrored_into_a_private_chat_thread(): void
    {
        DB::beginTransaction();

        try {
            $admin = $this->platformAdmin();
            $student = $this->studentUser();

            $m = app(PlatformMessagingService::class)->sendFromPlatform(
                actor: $admin,
                subject: 'Bridge Test',
                body: 'Hello student.',
                scope: 'users',
                userIds: [$student->id],
            );

            $thread = ChatThread::withoutTenantScope()
                ->where('school_id', $student->school_id)
                ->where('metadata->platform_thread_id', $m->thread_id)
                ->first();

            $this->assertNotNull($thread, 'A bridged chat thread must be created for the target user.');
            $this->assertTrue($thread->users()->where('users.id', $student->id)->exists());
            $this->assertDatabaseHas('communication_chat_messages', [
                'thread_id' => $thread->id,
                'sender_id' => $admin->id,
            ]);

            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $student->id,
                'type' => PlatformMessageNotification::class,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_student_reply_and_platform_reply_stay_inside_the_same_bridged_thread(): void
    {
        DB::beginTransaction();

        try {
            $admin = $this->platformAdmin();
            $student = $this->studentUser();

            $m = app(PlatformMessagingService::class)->sendFromPlatform(
                actor: $admin,
                subject: 'Bridge Circle',
                body: 'Are you there?',
                scope: 'users',
                userIds: [$student->id],
            );

            $thread = ChatThread::withoutTenantScope()
                ->where('school_id', $student->school_id)
                ->where('metadata->platform_thread_id', $m->thread_id)
                ->firstOrFail();

            // Student replies from their portal Chat -> back to the platform.
            $studentReply = ChatMessage::create([
                'school_id' => $thread->school_id,
                'thread_id' => $thread->id,
                'sender_id' => $student->id,
                'message' => 'Yes, here.',
            ]);
            PlatformChatBridge::mirrorChatReplyToPlatform($studentReply);

            $this->assertDatabaseHas('platform_messages', [
                'sender_type' => 'school',
                'sender_user_id' => $student->id,
                'thread_id' => $m->thread_id,
            ]);

            // Platform reply -> mirrored back into the same chat thread.
            $reply = app(PlatformMessagingService::class)->replyFromPlatform($admin, $m, 'Great, see you at assembly.');
            $this->assertSame($m->thread_id, $reply->thread_id);

            $this->assertDatabaseHas('communication_chat_messages', [
                'thread_id' => $thread->id,
                'sender_id' => $admin->id,
                'message' => 'Great, see you at assembly.',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_user_targeted_messages_are_hidden_from_the_rest_of_the_school_inbox(): void
    {
        DB::beginTransaction();

        try {
            $admin = $this->platformAdmin();
            $student = $this->studentUser();

            // A second user from the same school who must never see the message.
            $other = User::query()
                ->where('school_id', $student->school_id)
                ->where('id', '!=', $student->id)
                ->firstOrFail();

            $m = app(PlatformMessagingService::class)->sendFromPlatform(
                actor: $admin,
                subject: 'Private to student',
                body: 'For your eyes only.',
                scope: 'users',
                userIds: [$student->id],
            );

            $inboxFor = fn (User $user) => PlatformMessage::withoutTenantScope()
                ->where(function ($q) use ($user) {
                    $q->where(fn ($q2) => $q2->where('sender_type', 'school')->where('school_id', $user->school_id))
                        ->orWhere(function ($q2) use ($user) {
                            $q2->where('sender_type', 'platform')
                                ->whereIn('id', PlatformMessageRecipient::query()
                                    ->where('school_id', $user->school_id)
                                    ->pluck('message_id'))
                                ->where(function ($q3) use ($user) {
                                    $q3->where(fn ($q4) => $q4->where('recipient_scope', '!=', 'users')->orWhereNull('recipient_scope'))
                                        ->orWhere(fn ($q4) => $q4->where('recipient_scope', 'users')->whereJsonContains('target_meta->user_ids', $user->id))
                                        ->orWhere(fn ($q4) => $q4->where('recipient_scope', 'users')->where('sender_user_id', $user->id));
                                });
                        });
                })
                ->pluck('id');

            $this->assertContains($m->id, $inboxFor($student), 'The targeted student must see the private message.');
            $this->assertNotContains($m->id, $inboxFor($other), 'Other school users must NOT see the private message.');
        } finally {
            DB::rollBack();
        }
    }
}
