<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchIndexSmokeTest extends TestCase
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

    public function test_search_index_includes_non_navigating_pages_for_school_admins(): void
    {
        $user = User::findOrFail(13);
        Auth::loginUsingId(13);
        $this->assertNotNull($user->school_id);

        $response = $this->actingAs($user)
            ->get('https://tinwayacademy.lvh.me/workspace');

        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('__workspaceSearchIndex', $html);
        $this->assertStringContainsString('/workspace/platform-inboxes', $html);

        // Tenant must never see platform URLs in their search index.
        $json = explode('__workspaceSearchIndex = ', $html)[1] ?? '';
        $this->assertStringNotContainsString('/platform/', substr($json, 0, 20000));
    }
}
