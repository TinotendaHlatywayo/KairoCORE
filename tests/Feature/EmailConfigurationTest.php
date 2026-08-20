<?php

namespace Tests\Feature;

use App\Mail\AdmissionConfirmation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\EmailConfiguration;
use Modules\Admin\Notifications\EmailConfigurationMissingNotification;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Students\Models\Student;
use Tests\TestCase;

class EmailConfigurationTest extends TestCase
{
    use InteractsWithDatabase;

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

    private function makeSchool(): School
    {
        $school = School::where('subdomain', 'rujeko')->first()
            ?? School::first();

        $this->assertNotNull($school, 'A school record is required to run these tests.');

        return $school;
    }

    public function test_upsert_creates_single_row_per_school_and_category()
    {
        $school = $this->makeSchool();
        $service = app(TenantEmailConfigurationService::class);

        $config = $service->upsert($school, EmailCategory::Admissions, [
            'from_email' => 'admissions@school.edu',
            'from_name' => 'Admissions Office',
            'mailer' => 'platform',
            'is_enabled' => true,
        ]);

        $this->assertSame($school->id, (int) $config->school_id);
        $this->assertSame(EmailCategory::Admissions->value, $config->category);
        $this->assertTrue($config->is_enabled);

        // Re-running upsert for the same school + category must not duplicate.
        $config2 = $service->upsert($school, EmailCategory::Admissions, [
            'from_email' => 'admissions2@school.edu',
        ]);

        $count = EmailConfiguration::query()
            ->forSchool($school->id)
            ->category(EmailCategory::Admissions)
            ->count();

        $this->assertSame(1, $count);
        $this->assertSame('admissions2@school.edu', $config2->fresh()->from_email);

        $config->delete();
    }

    public function test_password_is_encrypted_at_rest()
    {
        $school = $this->makeSchool();
        $service = app(TenantEmailConfigurationService::class);

        $config = $service->upsert($school, EmailCategory::Communication, [
            'from_email' => 'comm@school.edu',
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'username' => 'comm@school.edu',
            'password' => 'super-secret-value',
            'is_enabled' => true,
        ]);

        $this->assertSame('super-secret-value', $config->password);
        $this->assertNotSame('super-secret-value', $config->getRawOriginal('password'));

        // A blank password on a later upsert must preserve the stored secret.
        $service->upsert($school, EmailCategory::Communication, [
            'from_email' => 'comm@school.edu',
            'password' => '',
        ]);
        $fresh = $config->fresh();
        $this->assertSame('super-secret-value', $fresh->password);

        $config->delete();
    }

    public function test_validate_rejects_platform_sender_reuse()
    {
        $school = $this->makeSchool();
        $service = app(TenantEmailConfigurationService::class);

        $config = new EmailConfiguration([
            'mailer' => 'platform',
            'from_email' => platform_email_address(),
            'is_enabled' => true,
        ]);

        $errors = $service->validate($config);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('platform', implode(' ', $errors));
    }

    public function test_validate_requires_smtp_credentials()
    {
        $service = app(TenantEmailConfigurationService::class);

        $config = new EmailConfiguration([
            'mailer' => 'smtp',
            'from_email' => 'admissions@school.edu',
            'host' => 'smtp.example.com',
            'username' => '',
            'password' => '',
            'is_enabled' => true,
        ]);

        $errors = $service->validate($config);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('SMTP username', implode(' ', $errors));
    }

    public function test_send_skips_when_configuration_missing_without_falling_back_to_platform()
    {
        $school = $this->makeSchool();

        $service = app(TenantEmailConfigurationService::class);

        $student = Student::withoutTenantScope()
            ->where('school_id', $school->id)
            ->first();

        if (! $student) {
            $student = Student::create([
                'school_id' => $school->id,
                'first_name' => 'Config',
                'last_name' => 'Fixture',
                'gender' => 'other',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $mailable = new AdmissionConfirmation(
            $student,
            'parent@example.com',
            'Subject',
            'Body',
            $school->name,
        );

        // No communication config exists for this school -> must refuse to send.
        $result = $service->send($mailable, EmailCategory::Communication, $school);
        $this->assertFalse($result);

        if ($student->wasRecentlyCreated) {
            $student->forceDelete();
        }
    }

    public function test_missing_config_notifies_admins_via_database_notification()
    {
        $school = $this->makeSchool();
        $service = app(TenantEmailConfigurationService::class);

        $admin = User::withoutTenantScope()
            ->where('school_id', $school->id)
            ->whereNotNull('custom_role_id')
            ->first();

        $before = DB::table('notifications')->count();

        $service->notifyMissingConfig($school, EmailCategory::Communication, 'No configuration.');

        $after = DB::table('notifications')->count();

        $this->assertGreaterThan($before, $after);

        if ($admin) {
            $this->assertNotNull(
                $admin->notifications()
                    ->where('type', EmailConfigurationMissingNotification::class)
                    ->latest()
                    ->first()
            );
        }
    }

    public function test_is_platform_email_helper()
    {
        $this->assertTrue(is_platform_email(platform_email_address()));
        $this->assertFalse(is_platform_email('admissions@school.edu'));
        $this->assertFalse(is_platform_email(''));
    }
}
