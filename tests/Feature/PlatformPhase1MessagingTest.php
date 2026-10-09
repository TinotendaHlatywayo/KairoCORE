<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Filament\Admin\Resources\PlatformMessageResource;
use App\Mail\SaaS\PlatformMessageMail;
use App\Models\School;
use App\Models\User;
use App\Notifications\PlatformMessageNotification;
use App\Services\SchoolRegistrationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\SaaS\Models\PlatformSetting;
use Modules\SaaS\Models\SaaSBillingSetting;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\PlatformMessagingService;
use Tests\TestCase;

/**
 * Phase 1 platform messaging / notification guarantees:
 *  - address settings resolve to hlatwayne@gmail.com by default and are editable
 *  - a tenant message emails the configured inbox AND lands in the platform
 *    notification centre
 *  - the platform can target specific users of a tenant (role/name filtered)
 *  - trial expiration actually suspends expired trials (and spares payers)
 */
class PlatformPhase1MessagingTest extends TestCase
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

    public function test_notification_address_settings_resolve_and_fall_back_to_the_default(): void
    {
        DB::beginTransaction();

        try {
            PlatformSetting::set('notifications', 'super_admin_email', 'registration@example.test');
            PlatformSetting::set('notifications', 'tenant_message_email', 'messages@example.test');
            PlatformSetting::set('notifications', 'system_from_email', 'sender@example.test');

            $this->assertSame('registration@example.test', platform_notification_email());
            $this->assertSame('messages@example.test', platform_tenant_message_email());
            $this->assertSame('sender@example.test', platform_system_from_email());

            $this->assertSame('registration@example.test', app(SchoolRegistrationService::class)->superAdminNotificationEmail());

            PlatformSetting::whereIn('key', ['super_admin_email', 'tenant_message_email', 'system_from_email'])->delete();

            $this->assertSame('hlatwayne@gmail.com', platform_notification_email());
            $this->assertSame('hlatwayne@gmail.com', platform_tenant_message_email());
        } finally {
            DB::rollBack();
        }
    }

    public function test_a_tenant_message_emails_the_inbox_and_notifies_the_platform_center(): void
    {
        DB::beginTransaction();

        try {
            PlatformSetting::set('notifications', 'tenant_message_email', 'alert-inbox@example.test');

            $school = $this->makeSchool();
            $actor = $this->makeUser($school, 'administrator');
            $superAdmin = User::whereNull('school_id')->firstOrFail();

            Mail::fake();
            Notification::fake();

            app(PlatformMessagingService::class)->sendFromSchool(
                actor: $actor,
                subject: 'Question about onboarding',
                body: 'How do we import our learner register?',
            );

            Mail::assertSent(PlatformMessageMail::class, function (PlatformMessageMail $mail) {
                return $mail->hasTo('alert-inbox@example.test');
            });

            Notification::assertSentTo($superAdmin, PlatformMessageNotification::class);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_platform_can_target_specific_users_of_a_tenant(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $target = $this->makeUser($school, 'teaching_staff');
            $bystander = $this->makeUser($school, 'non_teaching_staff');
            $actor = User::whereNull('school_id')->firstOrFail();

            Mail::fake();
            Notification::fake();

            $message = app(PlatformMessagingService::class)->sendFromPlatform(
                actor: $actor,
                subject: 'Staff briefing',
                body: 'Please review the new term timetable.',
                priority: 'info',
                scope: 'users',
                schoolIds: [$school->id],
                targetMeta: ['audience' => 'users', 'user_ids' => [$target->id]],
                channel: 'both',
                userIds: [$target->id],
            );

            Notification::assertSentTo($target, PlatformMessageNotification::class);
            Notification::assertNotSentTo($bystander, PlatformMessageNotification::class);

            Mail::assertSent(PlatformMessageMail::class, fn (PlatformMessageMail $mail) => $mail->hasTo($target->email));
            Mail::assertNotSent(PlatformMessageMail::class, fn (PlatformMessageMail $mail) => $mail->hasTo($bystander->email));

            $this->assertDatabaseHas('platform_message_recipients', [
                'message_id' => $message->id,
                'school_id' => $school->id,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_trial_expiration_suspends_expired_active_schools_but_spares_payers(): void
    {
        DB::beginTransaction();

        try {
            PlatformSetting::set('automation', 'auto_expire_trials', '1');

            $expired = $this->makeSchool(['status' => 'active', 'trial_ends_at' => now()->subDay()]);
            $payer = $this->makeSchool(['status' => 'active', 'trial_ends_at' => now()->subDay()]);

            $planId = SaaSPlan::query()->value('id');

            SaaSSubscription::create([
                'school_id' => $payer->id,
                'saas_plan_id' => $planId,
                'status' => 'active',
                'billing_period' => 'monthly',
            ]);

            $this->artisan('saas:expire-trials')->assertExitCode(0);

            $this->assertSame('suspended', $expired->fresh()->status);
            $this->assertSame('active', $payer->fresh()->status);
        } finally {
            DB::rollBack();
        }
    }

    public function test_trial_expiration_does_nothing_when_disabled(): void
    {
        DB::beginTransaction();

        try {
            PlatformSetting::set('automation', 'auto_expire_trials', '0');

            $expired = $this->makeSchool(['status' => 'active', 'trial_ends_at' => now()->subDay()]);

            $this->artisan('saas:expire-trials')->assertExitCode(0);

            $this->assertSame('active', $expired->fresh()->status);
        } finally {
            DB::rollBack();
        }
    }

    public function test_platform_settings_notifications_tab_persists_the_addresses(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(PlatformSettingsPage::class)
                ->fillForm([
                    'branding_platform_name' => 'Kairo CORE',
                    'notifications_super_admin_email' => 'reg@example.test',
                    'notifications_tenant_message_email' => 'msg@example.test',
                    'notifications_system_from_email' => 'from@example.test',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('reg@example.test', platform_notification_email());
            $this->assertSame('msg@example.test', platform_tenant_message_email());
            $this->assertSame('from@example.test', platform_system_from_email());
        } finally {
            DB::rollBack();
        }
    }

    public function test_recipient_user_search_filters_by_role_and_name(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $teacher = $this->makeUser($school, 'teaching_staff');
            $admin = $this->makeUser($school, 'administrator');

            $method = new \ReflectionMethod(PlatformMessageResource::class, 'recipientUserOptions');
            $method->setAccessible(true);

            $byRole = $method->invoke(null, $school->id, 'teaching_staff', null);
            $this->assertArrayHasKey($teacher->id, $byRole);
            $this->assertArrayNotHasKey($admin->id, $byRole);

            $bySearch = $method->invoke(null, $school->id, null, $teacher->email);
            $this->assertArrayHasKey($teacher->id, $bySearch);
            $this->assertArrayNotHasKey($admin->id, $bySearch);
        } finally {
            DB::rollBack();
        }
    }

    public function test_paynow_credentials_saved_in_billing_settings_are_resolved(): void
    {
        DB::beginTransaction();

        try {
            SaaSBillingSetting::getActiveSettings()->update([
                'paynow_integration_id' => 'test-id-123',
                'paynow_integration_key' => 'test-key-123',
                'paynow_merchant_email' => 'paynow@example.test',
            ]);

            $fresh = SaaSBillingSetting::getActiveSettings();

            $this->assertSame('test-id-123', $fresh->resolvedPaynowIntegrationId());
            $this->assertSame('test-key-123', $fresh->resolvedPaynowIntegrationKey());
            $this->assertSame('paynow@example.test', $fresh->resolvedPaynowMerchantEmail());
        } finally {
            DB::rollBack();
        }
    }

    private function makeSchool(array $attributes = []): School
    {
        return School::create(array_merge([
            'name' => 'Phase One School '.substr(uniqid(), -6),
            'subdomain' => 'phase-one-'.substr(uniqid(), -8),
            'status' => 'active',
        ], $attributes));
    }

    private function makeUser(School $school, string $role): User
    {
        $suffix = substr(uniqid(), -8);

        return User::create([
            'school_id' => $school->id,
            'name' => 'Phase One '.$role.' '.$suffix,
            'email' => 'phase-one-'.$suffix.'@example.test',
            'username' => 'phase-one-'.$suffix,
            'password' => 'Password@12345',
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => $role,
        ]);
    }
}
