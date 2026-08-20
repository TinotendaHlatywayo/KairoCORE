<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Auth\Login;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Students\Models\Student;
use Tests\TestCase;

class LoginRegistrationTest extends TestCase
{
    private School $school;

    private string $studentId = '';

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

        $this->studentId = 'TEST-REG-'.substr(uniqid(), -6);

        $this->school = School::create([
            'name' => 'Login Registration Test School',
            'subdomain' => 'login-reg-test-'.substr(uniqid(), -6),
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'student_id_number' => $this->studentId,
            'admission_number' => $this->studentId,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'other',
            'date_of_birth' => '2010-01-01',
            'admission_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        User::query()->where('school_id', $this->school->id)->delete();
        Student::query()->where('school_id', $this->school->id)->delete();
        $this->school->delete();
        parent::tearDown();
    }

    private function bindTenant(): void
    {
        app()->instance('current_tenant', $this->school);

        // registerAccount() rate-limits to 3 attempts per IP+method; clear the
        // limiter so repeated test runs never trip it.
        Cache::flush();
    }

    public function test_register_rejects_name_longer_than_100(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', str_repeat('A', 101))
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', 'name_max@example.com')
            ->set('regRole', 'student')
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regName' => 'max']);
    }

    public function test_register_rejects_invalid_email_format(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', 'Tendai Moyo')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', 'not-an-email')
            ->set('regRole', 'student')
            ->call('registerAccount')
            ->assertHasErrors(['regEmail' => 'email']);
    }

    public function test_register_reactivates_soft_deleted_account_instead_of_unique_violation(): void
    {
        $this->bindTenant();

        $email = 'reactivate-'.substr(uniqid(), -6).'@example.com';

        $deleted = User::create([
            'school_id' => $this->school->id,
            'name' => 'Old Pending',
            'email' => $email,
            'username' => $this->studentId,
            'password' => Hash::make(Str::random(64)),
            'account_status' => User::STATUS_PENDING,
            'requested_role' => 'student',
        ]);
        $deleted->delete();
        $deletedId = $deleted->id;

        Livewire::test(Login::class)
            ->set('regName', 'Tendai Moyo')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', $email)
            ->set('regRole', 'student')
            ->call('registerAccount')
            ->assertSet('regSubmitted', true);

        $restored = User::withTrashed()->find($deletedId);
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at, 'Soft-deleted account should have been restored.');
        $this->assertSame('Tendai Moyo', $restored->name);
        $this->assertSame(User::STATUS_PENDING, $restored->account_status);

        $this->assertSame(
            1,
            User::withTrashed()->where('school_id', $this->school->id)->where('email', $email)->count(),
            'Exactly one row must exist for the email (no unique-constraint violation).'
        );
    }
}
