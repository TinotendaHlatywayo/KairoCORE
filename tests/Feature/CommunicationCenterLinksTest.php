<?php

namespace Tests\Feature;

use App\Filament\App\Pages\CommunicationCenter;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Every tile on the Communication Center is a doorway: clicking it has to
 * arrive at the page that owns the thing it counts.
 */
class CommunicationCenterLinksTest extends TestCase
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

    public function test_every_tile_points_at_the_page_that_owns_it(): void
    {
        DB::beginTransaction();

        try {
            $school = School::create([
                'name' => 'Link School '.substr(uniqid(), -6),
                'subdomain' => 'links-'.substr(uniqid(), -8),
                'status' => 'active',
            ]);

            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Link Tester',
                'email' => 'links-'.substr(uniqid(), -8).'@example.test',
                'username' => 'links-'.substr(uniqid(), -8),
                'password' => 'Password@12345',
                'account_status' => User::STATUS_ACTIVE,
                'requested_role' => 'teaching_staff',
            ]);

            app()->instance('current_tenant', $school);
            URL::defaults(['tenant' => $school->subdomain]);

            $this->actingAs($user);

            $page = new CommunicationCenter;
            $kpis = $page->getViewData()['kpis'];

            $this->assertNotEmpty($kpis);

            $paths = [];

            foreach ($kpis as $kpi) {
                $this->assertArrayHasKey('link', $kpi, 'The tile ['.$kpi['label'].'] has no link.');

                $link = $kpi['link'];

                $this->assertNotSame('#', $link, 'The tile ['.$kpi['label'].'] still points nowhere.');

                $paths[$kpi['label']] = parse_url($link, PHP_URL_PATH);
            }

            // Each tile opens the resource that owns its number.
            $this->assertSame('/workspace/announcements', $paths['Open Announcements']);
            $this->assertSame('/workspace/event-calendars', $paths['Upcoming Events']);
            $this->assertSame('/workspace/helpdesk-tickets', $paths['Open Helpdesk Tickets']);
            $this->assertSame('/workspace/chat-threads', $paths['Active Chat Threads']);
            $this->assertSame('/workspace/campus-resources', $paths['Campus Resources']);
            $this->assertSame('/workspace/polls', $paths['Active Polls']);
        } finally {
            DB::rollBack();
        }
    }
}
