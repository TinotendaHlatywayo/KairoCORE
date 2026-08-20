<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\SaaS\Services\PlatformMessagingService;
use Tests\TestCase;

class PlatformMessagingTest extends TestCase
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

    private function schoolAdmin(): User
    {
        $school = School::query()->firstOrFail();

        $adminRole = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->first();

        if ($adminRole) {
            $admin = User::where('school_id', $school->id)->where('custom_role_id', $adminRole->id)->first();
            if ($admin) {
                return $admin;
            }
        }

        // PlatformInboxResource gates on 'communication.contact_platform', which
        // only the Administrator role bypasses, so provision one for the fixture.
        $user = User::where('school_id', $school->id)->firstOrFail();

        if (! $adminRole) {
            $adminRole = CustomRole::create([
                'school_id' => $school->id,
                'name' => 'Administrator',
                'description' => 'Test fixture administrator role.',
                'permissions' => [],
                'is_system' => true,
            ]);
        }

        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $user->forceFill(['custom_role_id' => $adminRole->id, 'account_status' => 'active'])->save();

        return $user->fresh();
    }

    public function test_admin_message_list_renders(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->get('http://lvh.me/platform/platform-messages')
            ->assertOk();
    }

    public function test_tenant_inbox_renders(): void
    {
        $user = $this->schoolAdmin();

        $this->actingAs($user)
            ->get('http://lvh.me/workspace/platform-inboxes')
            ->assertOk();
    }

    public function test_service_persists_message_and_recipients(): void
    {
        $admin = $this->platformAdmin();
        $user = $this->schoolAdmin();
        $svc = app(PlatformMessagingService::class);

        $m = $svc->sendFromPlatform($admin, 'Temp Test Subject', 'Temp body.', 'info', 'all', School::pluck('id')->all());
        $this->assertDatabaseHas('platform_messages', ['id' => $m->id, 'sender_type' => 'platform', 'recipient_scope' => 'all']);
        $this->assertGreaterThan(0, $m->recipients()->count());

        $sent = $svc->sendFromSchool($user, 'Temp Test From School', 'Tenant body.', 'normal');
        $this->assertDatabaseHas('platform_messages', ['id' => $sent->id, 'sender_type' => 'school', 'recipient_type' => 'platform']);
        $this->assertSame((int) $user->school_id, (int) $sent->school_id);
    }
}
