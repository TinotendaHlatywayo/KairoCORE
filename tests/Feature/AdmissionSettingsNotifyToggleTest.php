<?php

namespace Tests\Feature;

use App\Filament\App\Pages\AdmissionSettingsPage;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\EmailConfiguration;
use Modules\Admin\Models\SystemSetting;
use Tests\TestCase;

/**
 * "Email the Admission Contact Address on New Applications" must be enough
 * on its own: saving it ON enables the admissions email channel instead of
 * being silently blocked by a second, hidden is_enabled toggle.
 */
class AdmissionSettingsNotifyToggleTest extends TestCase
{
    private School $school;

    private ?string $admissionsModuleSaved = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));

        $this->school = School::where('subdomain', 'chiwariraprimary')->first()
            ?? School::firstOrFail();

        $this->actingAsTenant($this->school);

        $this->admissionsModuleSaved = SystemSetting::get('modules', 'admissions', '1');
        SystemSetting::set('modules', 'admissions', '1');

        $roleId = CustomRole::where('school_id', $this->school->id)
            ->where('role_key', 'administrator')
            ->value('id');
        $admin = User::where('school_id', $this->school->id)
            ->where('custom_role_id', $roleId)
            ->firstOrFail();
        $this->actingAs($admin);

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        if ($this->admissionsModuleSaved !== null) {
            SystemSetting::set('modules', 'admissions', $this->admissionsModuleSaved);
        }

        parent::tearDown();
    }

    public function test_saving_the_new_application_alert_toggle_enables_the_admissions_channel(): void
    {
        $config = EmailConfiguration::where('school_id', $this->school->id)
            ->where('category', 'admissions')
            ->firstOrFail();

        $settingsBefore = SystemSetting::where('school_id', $this->school->id)
            ->where('group', 'admission')
            ->pluck('value', 'key')
            ->all();
        $configBefore = $config->getAttributes();

        try {
            $config->update(['is_enabled' => false]);

            Livewire::test(AdmissionSettingsPage::class)
                ->set('data.admission_email', 'contact@fixture.test')
                ->set('data.admission_phone', '+263771234567')
                ->set('data.notify_email_enabled', true)
                ->set('data.emailcfg.admissions.is_enabled', false)
                ->call('save')
                ->assertHasNoFormErrors();

            $config->refresh();
            $this->assertTrue(
                $config->is_enabled,
                'Flipping the New Application alert toggle ON must enable the admissions email channel.'
            );
            $this->assertEquals('1', SystemSetting::get('admission', 'email_enabled', '0'));
        } finally {
            EmailConfiguration::where('id', $config->id)->update($configBefore);

            foreach ($settingsBefore as $key => $value) {
                SystemSetting::set('admission', $key, $value);
            }
        }
    }
}
