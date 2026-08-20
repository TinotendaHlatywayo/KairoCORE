<?php

namespace Tests;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PanelRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', '3306');
        config()->set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        config()->set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_panel_renders_notification_bell()
    {
        $school = School::where('subdomain', 'rujeko')->first();
        $user = User::withoutTenantScope()->where('school_id', $school->id)->where('email', 'admin@rujeko.edu')->first();

        $this->actingAs($user);
        app()->instance('current_tenant', $school);

        $response = $this->withHeader('Host', 'rujeko.lvh.me:8000')->get('/workspace');

        $html = $response->getContent();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('admission-notification-center', $html);
    }
}
