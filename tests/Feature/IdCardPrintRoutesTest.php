<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Student;
use Tests\TestCase;

class IdCardPrintRoutesTest extends TestCase
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

    public function test_print_cards_route_is_not_captured_by_view_record(): void
    {
        $school = School::where('subdomain', 'rujeko')->first();
        if (! $school) {
            $school = School::create(['name' => 'Rujeko High', 'subdomain' => 'rujeko', 'status' => 'active']);
        }

        $user = User::where('school_id', $school->id)->first();
        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Admin',
                'email' => 'admin@rujeko.test',
                'password' => bcrypt('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);
        }
        PermissionRegistry::ensureAdminHasRole($user, $user->school_id);
        $roleId = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->value('id');
        $user->forceFill(['custom_role_id' => $roleId, 'account_status' => 'active'])->save();

        $student = Student::where('school_id', $school->id)->where('status', 'active')->first();
        if (! $student) {
            $student = Student::create([
                'school_id' => $school->id,
                'first_name' => 'Card',
                'last_name' => 'Fixture',
                'gender' => 'other',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        $response = $this->get('http://rujeko.lvh.me/workspace/students/cards/print?ids='.$student->id.'&layout=pvc');

        $this->assertNotEquals(404, $response->getStatusCode(), $response->getStatusCode().' - check Filament view route collision');
    }
}
