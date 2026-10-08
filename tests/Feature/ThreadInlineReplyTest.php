<?php

namespace Tests\Feature;

use App\Filament\App\Resources\PlatformInboxResource\Pages\ListPlatformInboxes;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Services\PlatformMessagingService;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $school = School::query()->where('subdomain', 'chiwariraprimary')->firstOrFail();

        $adminRole = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->firstOrFail();
        $admin = User::where('school_id', $school->id)->where('custom_role_id', $adminRole->id)->firstOrFail();

        return $admin;
    }

    public function test_school_admin_can_reply_from_view_thread_modal(): void
    {
        DB::beginTransaction();
        try {
            $admin = $this->schoolAdmin();
            $this->actingAs($admin);

            $service = app(PlatformMessagingService::class);
            $sent = null;
            Auth::login($admin);
            $sent = $service->sendFromSchool($admin, 'Thread reply test '.uniqid(), 'Original body');

            // The root message of this school's newest thread.
            $parent = PlatformMessage::withoutGlobalScopes()
                ->where('school_id', $admin->school_id)
                ->where('sender_type', 'school')
                ->latest('id')
                ->firstOrFail();

            $component = new ListPlatformInboxes;
            $component->threadReplyBody = 'Inline reply body';
            $component->sendThreadReply($parent->id);

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

            $this->expectException(HttpException::class);
            $component->sendThreadReply($foreignParent->id);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Regression guard for the reported bug: the View Thread modal is rendered
     * inside Filament's own table-action <form>, so the composer must NOT nest a
     * second <form> (invalid HTML → browser drops it → Send did nothing). The
     * composer must submit via wire:click instead.
     */
    public function test_thread_composer_does_not_nest_a_form_and_uses_wire_click(): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag));

        $html = view('filament.admin.resources.platform-message-thread', [
            'messages' => collect(),
            'threadParentId' => 42,
            'canReply' => true,
            'viewerSchoolId' => null,
        ])->render();

        $this->assertStringContainsString('wire:click="sendThreadReply(42)"', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_view_thread_modal_reply_sends_through_livewire(): void
    {
        DB::beginTransaction();
        try {
            $admin = $this->schoolAdmin();
            $this->actingAs($admin);

            $service = app(PlatformMessagingService::class);
            $root = $service->sendFromSchool($admin, 'Livewire modal '.uniqid(), 'root body');

            Livewire::actingAs($admin)
                ->test(ListPlatformInboxes::class)
                ->mountTableAction('view_thread', $root)
                ->assertSeeHtml('wire:click="sendThreadReply(')
                ->set('threadReplyBody', 'reply through livewire modal')
                ->call('sendThreadReply', $root->id)
                ->assertHasNoErrors();

            $this->assertDatabaseHas('platform_messages', [
                'thread_id' => $root->thread_id,
                'sender_type' => 'school',
                'body' => 'reply through livewire modal',
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }
}
