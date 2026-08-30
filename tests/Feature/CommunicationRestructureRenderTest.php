<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;

class CommunicationRestructureRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $school = School::find(5);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    public function test_communication_pages_and_hubs_render(): void
    {
        $user = User::find(15);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        // Hub URLs redirect to the remembered/first page in the category.
        foreach ([
            '/workspace/communication-schedule-tasks',
            '/workspace/communication-community',
            '/workspace/communication-help-inbox',
        ] as $url) {
            $this->get($url)->assertRedirect();
        }

        $this->get('/workspace/communication-center')->assertOk();

        // The schedule and my-day pages must still render under a real user.
        $this->get('/workspace/schedule')->assertOk();
        $this->get('/workspace/my-day')->assertOk();
    }
}
