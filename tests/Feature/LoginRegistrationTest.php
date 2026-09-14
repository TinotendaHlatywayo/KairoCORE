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

    private string $studentEmail = '';

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
        $this->studentEmail = 'test-student-'.substr(uniqid(), -6).'@example.com';

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
            'email' => $this->studentEmail,
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

        // Full name is only collected for administrators — the student's name
        // comes from the roster record instead. Keep the max-length guard on
        // the administrator path.
        Livewire::test(Login::class)
            ->set('regName', str_repeat('A', 101))
            ->set('regIdentifier', null)
            ->set('regEmail', 'admin_reg@example.com')
            ->set('regRole', 'administrator')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regName' => 'max']);
    }

    public function test_register_invalid_email_format_is_rejected_before_roster_lookup(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', 'not-an-email')
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regEmail' => 'email']);
    }

    public function test_register_uses_full_name_from_roster_as_username_and_links_student(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', $this->studentEmail)
            ->set('regPhone', '+263 771 000 111')
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertSet('regSubmitted', true)
            ->assertSet('regSubmittedName', 'Test Student');

        $student = Student::query()->where('student_id_number', $this->studentId)->first();
        $user = User::find($student->user_id);

        $this->assertNotNull($user, 'A pending portal account should be linked to the student.');
        $this->assertSame('Test Student', $user->name);
        $this->assertSame('Test Student', $user->username);
        $this->assertSame($this->studentEmail, $user->email);
        $this->assertSame(User::STATUS_PENDING, $user->account_status);
        $this->assertSame('student', $user->requested_role);
    }

    public function test_register_rejects_email_that_does_not_match_the_roster(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', 'someone-else@example.com')
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regEmail'])
            ->assertSet('regSubmitted', false);
    }

    public function test_register_rejects_unknown_identifier(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', 'DOES-NOT-EXIST')
            ->set('regEmail', $this->studentEmail)
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regIdentifier'])
            ->assertSet('regSubmitted', false);
    }

    public function test_register_second_attempt_tells_existing_user_to_sign_in(): void
    {
        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', $this->studentEmail)
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertSet('regSubmitted', true);

        // Register again with the same details — the account now exists, so the
        // applicant must be told to sign in instead of creating a duplicate.
        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', $this->studentEmail)
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertStatus(200)
            ->assertHasErrors(['regEmail'])
            ->assertSet('regSubmitted', false);

        $this->assertSame(
            1,
            User::query()->where('school_id', $this->school->id)->where('email', $this->studentEmail)->count(),
            'Exactly one account may exist for the student email.'
        );
    }

    public function test_register_reactivates_soft_deleted_account_instead_of_unique_violation(): void
    {
        $this->bindTenant();

        $deleted = User::create([
            'school_id' => $this->school->id,
            'name' => 'Old Pending',
            'email' => $this->studentEmail,
            'username' => $this->studentId,
            'password' => Hash::make(Str::random(64)),
            'account_status' => User::STATUS_PENDING,
            'requested_role' => 'student',
        ]);
        $deleted->delete();
        $deletedId = $deleted->id;

        Livewire::test(Login::class)
            ->set('regName', '')
            ->set('regIdentifier', $this->studentId)
            ->set('regEmail', $this->studentEmail)
            ->set('regRole', 'student')
            ->set('regAgreeTerms', true)
            ->call('registerAccount')
            ->assertSet('regSubmitted', true);

        $restored = User::withTrashed()->find($deletedId);
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at, 'Soft-deleted account should have been restored.');
        $this->assertSame('Test Student', $restored->name);
        $this->assertSame('Test Student', $restored->username);
        $this->assertSame(User::STATUS_PENDING, $restored->account_status);

        $this->assertSame(
            1,
            User::withTrashed()->where('school_id', $this->school->id)->where('email', $this->studentEmail)->count(),
            'Exactly one row must exist for the email (no unique-constraint violation).'
        );
    }
}
