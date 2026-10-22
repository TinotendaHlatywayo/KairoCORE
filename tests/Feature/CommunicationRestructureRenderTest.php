<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Modules\Admin\Models\CustomRole;

class CommunicationRestructureRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        // The school and the account are looked up rather than hard-coded, so
        // the test runs against whatever tenant the database actually holds.
        // The school has to be active (a suspended tenant is turned away by
        // the middleware) and the account has to be its system administrator,
        // because the communication pages are permission gated.
        $school = School::query()
            ->where('status', 'active')
            ->whereHas('users', fn ($query) => $query->whereNotNull('custom_role_id'))
            ->firstOrFail();

        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    /**
     * The school's system administrator.
     */
    private function administrator(): User
    {
        $school = current_tenant();

        $adminRoleId = CustomRole::withoutTenantScope()
            ->where('school_id', $school?->id)
            ->where('role_key', 'administrator')
            ->value('id');

        return User::withoutGlobalScopes()
            ->where('school_id', $school?->id)
            ->where('account_status', User::STATUS_ACTIVE)
            ->where(function ($query) use ($adminRoleId) {
                $query->where('custom_role_id', $adminRoleId);

                if ($adminRoleId === null) {
                    $query->orWhere('requested_role', 'administrator');
                }
            })
            ->firstOrFail();
    }

    public function test_communication_pages_render(): void
    {
        $this->actingAs($this->administrator())
            ->withServerVariables(['HTTP_HOST' => current_tenant()->subdomain.'.lvh.me:8000']);

        $this->get('/workspace/communication-center')->assertOk();

        // The schedule and my-day pages must still render under a real user.
        $this->get('/workspace/schedule')->assertOk();
        $this->get('/workspace/my-day')->assertOk();
    }

    /**
     * My Day and the Schedule are part of the Communication Center, so the
     * module header must offer both next to the Overview tab.
     */
    public function test_the_communication_center_offers_my_day_and_the_schedule(): void
    {
        $this->actingAs($this->administrator())
            ->withServerVariables(['HTTP_HOST' => current_tenant()->subdomain.'.lvh.me:8000']);

        $response = $this->get('/workspace/communication-center')->assertOk();

        $response->assertSee('My Day');
        $response->assertSee('Schedule');
    }
}
