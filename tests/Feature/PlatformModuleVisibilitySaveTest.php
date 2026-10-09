<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SchoolResource\Pages\EditSchool;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Models\SystemSetting;
use Tests\TestCase;

/**
 * The Module Visibility switches live on the platform's school edit form.
 * They are not a column on `schools`, so they have to be moved into
 * system_settings by the page save hook — the resource-level hook that used to
 * hold this logic is a Filament v2 API that v3 never calls, which meant a
 * switch flipped off for a tenant was written nowhere and simply did nothing.
 */
class PlatformModuleVisibilitySaveTest extends TestCase
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

    public function test_saving_a_school_writes_the_module_toggles(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $school = School::findOrFail((int) config('tenancy.single_tenant_id'));

            foreach (['inventory' => '1', 'reports' => '1'] as $key => $value) {
                SystemSetting::withoutTenantScope()->updateOrCreate(
                    ['school_id' => $school->id, 'group' => 'modules', 'key' => $key],
                    ['value' => $value],
                );
            }

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(EditSchool::class, ['record' => $school->getKey()])
                ->fillForm([
                    'modules' => [
                        'inventory' => false,
                        'reports' => false,
                    ],
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            foreach (['inventory', 'reports'] as $key) {
                $this->assertSame(
                    '0',
                    SystemSetting::withoutTenantScope()
                        ->where('school_id', $school->id)
                        ->where('group', 'modules')
                        ->where('key', $key)
                        ->value('value'),
                    "Switching {$key} off must be persisted to system_settings.",
                );
            }
        } finally {
            DB::rollBack();
        }
    }
}
