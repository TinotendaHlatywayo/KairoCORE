<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceAnalyticsRenderTest extends TestCase
{
    use WithFaker;

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

    public function test_performance_analytics_page_renders(): void
    {
        $school = School::where('subdomain', 'chiwariraprimary')->first() ?? School::first();
        $this->assertNotNull($school);

        $user = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereNotNull('password')
            ->first() ?? User::first();

        $this->assertNotNull($user);

        $this->actingAs($user);
        $this->actingAsTenant($school);

        $response = $this->get('/workspace/exams-performance-analytics');

        $response->assertStatus(200);

        $response->assertSee('Filter Analytics');
        $response->assertSee('exams-performance-analytics');

        // The term/course/section/subject filters are Filament form fields
        // (styled selects), not raw HTML <select> elements.
        $response->assertSee('fi-fo-select', false);
        $response->assertDontSee('pa-filter');
    }
}