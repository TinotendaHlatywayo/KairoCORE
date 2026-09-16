<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Notifications\SchoolRegisteredNotification;
use App\Services\SchoolRegistrationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Subject;
use Tests\TestCase;

/**
 * TEMPORARY proof (Temp* — never commit). Proves, against the real local DB,
 * both guarantees the platform owner asked for.
 */
class TempProofRegistrationEmailAndSubjectDeleteTest extends TestCase
{
    private School $school;

    private User $contact;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', 'root');
        Config::set('database.connections.mysql.password', '');
        DB::purge('mysql');

        $this->school = School::create([
            'name' => 'Temp Email Proof School '.substr(uniqid(), -5),
            'subdomain' => 'temp-email-proof-'.substr(uniqid(), -5),
            'status' => 'pending',
        ]);

        $this->contact = User::create([
            'school_id' => $this->school->id,
            'name' => 'Temp Registration Contact',
            'email' => 'temp-reg-contact-'.substr(uniqid(), -6).'@example.com',
            'username' => 'temp-reg-contact-'.substr(uniqid(), -6),
            'password' => 'secret',
            'account_status' => 'pending',
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('subjects')->where('school_id', $this->school->id)->delete();
        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $this->contact->id)
            ->delete();
        $this->contact->forceDelete();
        $this->school->forceDelete();
        parent::tearDown();
    }

    /**
     * PROOF 1 — the new-school registration alert resolves to the platform
     * Gmail inbox, and the rendered alert names the registered school.
     */
    public function test_new_school_registration_alert_goes_to_the_platform_gmail_inbox(): void
    {
        $inbox = app(SchoolRegistrationService::class)->superAdminNotificationEmail();

        $this->assertNotFalse(filter_var($inbox, FILTER_VALIDATE_EMAIL));
        $this->assertStringContainsString(
            '@gmail.com',
            mb_strtolower($inbox),
            'The registration alert must be delivered to a Gmail inbox.'
        );

        $notification = new SchoolRegisteredNotification($this->school, $this->contact, true);
        $mail = $notification->toMail($this->contact);

        $this->assertStringContainsString(
            $this->school->name,
            $mail->subject,
            'The registration e-mail must name the specific registered school.'
        );
    }

    /**
     * PROOF 2 — deleting a subject hard-removes its row from the database.
     */
    public function test_deleting_a_subject_hard_removes_its_database_row(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Temp Delete Proof Subject '.substr(uniqid(), -5),
            'code' => 'DEL-'.substr(uniqid(), -5),
        ]);
        $id = $subject->id;

        $subject->delete();

        $this->assertFalse(
            DB::table('subjects')->where('id', $id)->exists(),
            'Deleting a subject must remove its row from the database.'
        );
    }
}
