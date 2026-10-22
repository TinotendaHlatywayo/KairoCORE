<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\PlatformAnnouncementResource\Pages\ListPlatformAnnouncements;
use App\Livewire\PlatformAnnouncementBanner;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\SaaS\Models\PlatformAnnouncement;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\PlatformAnnouncementService;
use Tests\TestCase;

/**
 * A platform announcement is written for tenants, so it has to say who it is
 * addressed to: every tenant, the system administrators only, chosen tenants,
 * or named individuals. The reader's own role decides whether they see it.
 */
class PlatformAnnouncementAudienceTest extends TestCase
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

    public function test_an_announcement_for_every_tenant_reaches_every_role(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $teacher = $this->makeUser($school, 'teaching_staff');
            $admin = $this->makeUser($school, 'administrator');

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_ALL,
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertTrue($service->visibleTo($announcement, $teacher));
            $this->assertTrue($service->visibleTo($announcement, $admin));
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_announcement_for_system_administrators_skips_the_other_roles(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $teacher = $this->makeUser($school, 'teaching_staff');
            $admin = $this->makeUser($school, 'administrator');

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_ADMINS,
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertFalse($service->visibleTo($announcement, $teacher));
            $this->assertTrue($service->visibleTo($announcement, $admin));
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_announcement_for_selected_tenants_reaches_only_those_tenants(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $otherSchool = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');
            $otherReader = $this->makeUser($otherSchool, 'teaching_staff');

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_TENANTS,
                'target_school_ids' => [$school->id],
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertTrue($service->visibleTo($announcement, $reader));
            $this->assertFalse($service->visibleTo($announcement, $otherReader));
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_announcement_for_specific_people_reaches_only_them(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');
            $bystander = $this->makeUser($school, 'librarian');

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_USERS,
                'target_user_ids' => [$reader->id],
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertTrue($service->visibleTo($announcement, $reader));
            $this->assertFalse($service->visibleTo($announcement, $bystander));
        } finally {
            DB::rollBack();
        }
    }

    public function test_a_role_narrowed_announcement_skips_the_other_roles(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $teacher = $this->makeUser($school, 'teaching_staff');
            $librarian = $this->makeUser($school, 'librarian');

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_ALL,
                'target_roles' => ['librarian'],
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertFalse($service->visibleTo($announcement, $teacher));
            $this->assertTrue($service->visibleTo($announcement, $librarian));
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_inactive_or_expired_announcement_is_not_shown(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');

            $inactive = $this->makeAnnouncement(['is_active' => false]);
            $expired = $this->makeAnnouncement(['ends_at' => now()->subDay()]);
            $future = $this->makeAnnouncement(['starts_at' => now()->addDay()]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertFalse($service->visibleTo($inactive, $reader));
            $this->assertFalse($service->visibleTo($expired, $reader));
            $this->assertFalse($service->visibleTo($future, $reader));
        } finally {
            DB::rollBack();
        }
    }

    public function test_a_platform_account_is_never_addressed_by_a_tenant_announcement(): void
    {
        DB::beginTransaction();

        try {
            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_ALL,
            ]);

            $platformUser = User::whereNull('school_id')->firstOrFail();

            $service = app(PlatformAnnouncementService::class);

            $this->assertFalse($service->visibleTo($announcement, $platformUser));
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_banner_shows_the_newest_announcement_addressed_to_the_reader(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');

            $this->makeAnnouncement([
                'title' => 'Older bulletin',
                'show_in_banner' => true,
            ]);

            $newest = $this->makeAnnouncement([
                'title' => 'Newer bulletin',
                'show_in_banner' => true,
            ]);

            $this->actingAs($reader);

            Livewire::test(PlatformAnnouncementBanner::class)
                ->assertSet('announcementId', $newest->id)
                ->assertSet('title', 'Newer bulletin');
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_banner_stays_hidden_once_the_reader_dismisses_it(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');

            $announcement = $this->makeAnnouncement([
                'title' => 'Dismiss me',
                'show_in_banner' => true,
            ]);

            $this->actingAs($reader);

            Livewire::test(PlatformAnnouncementBanner::class)
                ->assertSet('announcementId', $announcement->id)
                ->call('dismiss')
                ->assertSet('dismissed', true);

            $this->assertContains($announcement->id, (array) session('platform_announcements_dismissed'));

            // A fresh render of the banner must not bring it back.
            Livewire::test(PlatformAnnouncementBanner::class)
                ->assertSet('announcementId', null)
                ->assertSet('dismissed', false);
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_announcement_that_is_not_a_banner_is_not_shown_in_the_banner(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');

            $this->makeAnnouncement([
                'title' => 'Not a banner',
                'show_in_banner' => false,
            ]);

            $this->actingAs($reader);

            Livewire::test(PlatformAnnouncementBanner::class)
                ->assertSet('announcementId', null);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_banner_is_rendered_on_a_tenant_page(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $reader = $this->makeUser($school, 'teaching_staff');

            $this->makeAnnouncement([
                'title' => 'Billing window opens on Monday',
                'show_in_banner' => true,
            ]);

            App::instance('current_tenant', $school);
            URL::defaults(['tenant' => $school->subdomain]);

            $this->actingAs($reader);

            $response = $this->get('/workspace');

            $response->assertSuccessful();
            $response->assertSee('Billing window opens on Monday');
        } finally {
            DB::rollBack();
        }
    }

    public function test_an_announcement_written_for_a_plan_reaches_only_the_tenants_on_that_plan(): void
    {
        DB::beginTransaction();

        try {
            $plan = SaaSPlan::query()->firstOrFail();

            $subscriber = $this->makeSchool();
            $otherSchool = $this->makeSchool();

            $reader = $this->makeUser($subscriber, 'teaching_staff');
            $otherReader = $this->makeUser($otherSchool, 'teaching_staff');

            SaaSSubscription::create([
                'school_id' => $subscriber->id,
                'saas_plan_id' => $plan->id,
                'status' => 'active',
                'billing_period' => 'monthly',
            ]);

            $announcement = $this->makeAnnouncement([
                'audience' => PlatformAnnouncement::AUDIENCE_ALL,
                'target_plans' => [(string) $plan->id],
            ]);

            $service = app(PlatformAnnouncementService::class);

            $this->assertTrue($service->visibleTo($announcement, $reader));
            $this->assertFalse($service->visibleTo($announcement, $otherReader));
        } finally {
            DB::rollBack();
        }
    }

    /**
     * The platform writes announcements through the admin form, so the form
     * has to be able to store the audience, the roles and the banner flag.
     */
    public function test_the_platform_can_write_an_announcement_through_the_admin_form(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $this->actingAs($admin);

            // The announcements live on the /platform panel; make it current so
            // the table resolves its own routes while rendering.
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(ListPlatformAnnouncements::class)
                ->callAction('create', [
                    'title' => 'Billing window opens on Monday',
                    'content' => 'Term 2 fees open for payment on Monday.',
                    'type' => 'info',
                    'audience' => PlatformAnnouncement::AUDIENCE_ALL,
                    'target_roles' => ['librarian'],
                    'show_in_banner' => true,
                    'is_active' => true,
                ])
                ->assertHasNoActionErrors();

            $announcement = PlatformAnnouncement::query()->orderByDesc('id')->first();

            $this->assertNotNull($announcement);
            $this->assertSame('Billing window opens on Monday', $announcement->title);
            $this->assertSame(PlatformAnnouncement::AUDIENCE_ALL, $announcement->audience);
            $this->assertSame(['librarian'], $announcement->target_roles);
            $this->assertTrue((bool) $announcement->show_in_banner);
        } finally {
            DB::rollBack();
        }
    }

    private function makeAnnouncement(array $attributes = []): PlatformAnnouncement
    {
        return PlatformAnnouncement::create(array_merge([
            'title' => 'Bulletin '.substr(uniqid(), -6),
            'content' => 'Something worth reading.',
            'type' => 'info',
            'audience' => PlatformAnnouncement::AUDIENCE_ALL,
            'is_active' => true,
            'show_in_banner' => false,
        ], $attributes));
    }

    private function makeSchool(array $attributes = []): School
    {
        return School::create(array_merge([
            'name' => 'Audience School '.substr(uniqid(), -6),
            'subdomain' => 'audience-'.substr(uniqid(), -8),
            'status' => 'active',
        ], $attributes));
    }

    private function makeUser(School $school, string $role): User
    {
        $suffix = substr(uniqid(), -8);

        return User::create([
            'school_id' => $school->id,
            'name' => 'Audience '.$role.' '.$suffix,
            'email' => 'audience-'.$suffix.'@example.test',
            'username' => 'audience-'.$suffix,
            'password' => 'Password@12345',
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => $role,
        ]);
    }
}
