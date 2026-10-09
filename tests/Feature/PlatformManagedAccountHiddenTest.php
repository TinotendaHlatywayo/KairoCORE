<?php

namespace Tests\Feature;

use App\Filament\App\Resources\UserAccountResource;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The platform signs in to a school through a real, platform-managed user row
 * ("Enter school" creates system-administrator+{school}@...). That account has
 * to exist for the impersonation to work, but the school must never see it in
 * its own lists, pickers or searches — otherwise a tenant learns it is being
 * impersonated.
 */
class PlatformManagedAccountHiddenTest extends TestCase
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

    public function test_impersonation_accounts_are_not_listed_to_the_tenant(): void
    {
        DB::beginTransaction();

        try {
            $school = School::findOrFail((int) config('tenancy.single_tenant_id'));
            App::instance('current_tenant', $school);

            $shadow = User::create([
                'school_id' => $school->id,
                'name' => 'System Administrator',
                'email' => 'system-administrator+'.$school->id.'@kairocore.invalid',
                'password' => Hash::make('ShadowPass@1'),
                'account_status' => User::STATUS_ACTIVE,
                'requested_role' => 'administrator',
                'is_platform_managed' => true,
            ]);

            $normal = User::create([
                'school_id' => $school->id,
                'name' => 'Visible Staff Member',
                'email' => 'visible.staff.'.uniqid().'@example.test',
                'password' => Hash::make('NormalPass@1'),
                'account_status' => User::STATUS_ACTIVE,
                'requested_role' => 'non_teaching_staff',
                'is_platform_managed' => false,
            ]);

            // The scope every tenant-facing list should use.
            $visibleIds = User::query()->notPlatformManaged()->pluck('id');

            $this->assertFalse($visibleIds->contains($shadow->id), 'The impersonation account must be hidden.');
            $this->assertTrue($visibleIds->contains($normal->id), 'Real users must stay visible.');

            // And the User Accounts screen is wired to it.
            $listedIds = UserAccountResource::getEloquentQuery()->pluck('id');

            $this->assertFalse($listedIds->contains($shadow->id), 'The User Accounts page must not list the impersonation account.');
            $this->assertTrue($listedIds->contains($normal->id), 'The User Accounts page must list real users.');
        } finally {
            DB::rollBack();
        }
    }
}
