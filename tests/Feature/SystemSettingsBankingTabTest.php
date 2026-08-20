<?php

namespace Tests\Feature;

use App\Filament\App\Pages\SystemSettingsPage;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Finance\Models\SchoolBankAccount;
use Tests\TestCase;

class SystemSettingsBankingTabTest extends TestCase
{
    use InteractsWithDatabase;

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

    public function test_banking_tab_renders_invoice_account_select(): void
    {
        $school = School::firstOrFail();
        $admin = User::find(13);
        $this->actingAs($admin);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'app']);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        Livewire::test(SystemSettingsPage::class)
            ->assertOk()
            ->assertFormFieldExists('banking_invoice_default_bank_account_id');
    }

    public function test_banking_tab_can_save_invoice_account_selection(): void
    {
        $school = School::firstOrFail();
        $admin = User::find(13);
        $this->actingAs($admin);

        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'app']);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $account = SchoolBankAccount::create([
            'school_id' => $school->id,
            'bank_name' => 'Tab Test Bank',
            'account_name' => 'Tab School',
            'account_number' => '555555',
            'is_active' => true,
        ]);

        Livewire::test(SystemSettingsPage::class)
            ->fillForm([
                'banking_invoice_default_bank_account_id' => (string) $account->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = DB::table('system_settings')
            ->where('school_id', $school->id)
            ->where('group', 'banking')
            ->where('key', 'invoice_default_bank_account_id')
            ->first();

        $this->assertNotNull($setting);
        $this->assertSame((string) $account->id, $setting->value);

        SchoolBankAccount::withoutTenantScope()->where('school_id', $school->id)->where('bank_name', 'Tab Test Bank')->forceDelete();
        DB::table('system_settings')->where('school_id', $school->id)->where('group', 'banking')->where('key', 'invoice_default_bank_account_id')->delete();
    }
}
