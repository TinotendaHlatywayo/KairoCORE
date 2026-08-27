<?php

namespace Tests\Feature;

use App\Filament\App\Resources\PlatformInboxResource\Pages\ListPlatformInboxes;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\SaaS\Models\PlatformMessage;
use Tests\TestCase;

class ThreadInlineReplyTest extends TestCase
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

    private function schoolAdmin(): User
    {
        $school = School::query()->where('subdomain', 'tinwayacademy')->firstOrFail();

        $adminRole = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->firstOrFail();
        $admin = User::where('school_id', $school->id)->where('custom_role_id', $adminRole->id)->firstOrFail();

        return $admin;
    }

    public function test_school_admin_can_reply_from_view_thread_modal(): void
    {
        DB::beginTransaction();
        try {
            $admin = $this->schoolAdmin();
            $this->actingAs($admin);

            $service = app(\Modules\SaaS\Services\PlatformMessagingService::class);
            $sent = null;
            \Illuminate\Support\Facades\Auth::login($admin);
            $sent = $service->sendFromSchool($admin, 'Thread reply test '.uniqid(), 'Original body');

            // The root message of this school's newest thread.
            $parent = PlatformMessage::withoutGlobalScopes()
                ->where('school_id', $admin->school_id)
                ->where('sender_type', 'school')
                ->latest('id')
                ->firstOrFail();

            $component = new ListPlatformInboxes;
            $component->threadReplyParentId = $parent->id;
            $component->threadReplyBody = 'Inline reply body';
            $component->sendThreadReply();

            $this->assertDatabaseHas('platform_messages', [
                'thread_id' => $parent->thread_id,
                'sender_type' => 'school',
                'body' => 'Inline reply body',
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }

    public function test_tenant_cannot_reply_into_foreign_thread(): void
    {
        DB::beginTransaction();
        try {
            $this->actingAs($this->schoolAdmin());

            $otherSchool = School::whereKeyNot(auth()->user()->school_id)->firstOrFail();
            $foreignParent = PlatformMessage::withoutGlobalScopes()->create([
                'thread_id' => 'foreign-'.uniqid(),
                'sender_type' => 'platform',
                'school_id' => null,
                'subject' => 'Foreign',
                'body' => 'Foreign body',
            ]);
            DB::table('platform_message_recipients')->insert([
                'message_id' => $foreignParent->id,
                'school_id' => $otherSchool->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = new ListPlatformInboxes;
            $component->threadReplyParentId = $foreignParent->id;
            $component->threadReplyBody = 'hijack';

            $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
            $component->sendThreadReply();
        } finally {
            DB::rollBack();
        }
    }
}
