<?php

namespace Tests\Feature;

use App\Filament\App\Resources\FixedAssetResource\Pages\ListFixedAssets;
use App\Filament\App\Resources\ListEmployees;
use App\Filament\App\Resources\SupplierResource\Pages\ListSuppliers;
use App\Filament\App\Resources\ViewEmployee;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\HR\Models\Employee;
use Modules\Inventory\Filament\Resources\InventoryItemResource\Pages\ListInventoryItems;
use Tests\TestCase;

class CsvBulkActionsRenderTest extends TestCase
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

    private function tenantUser(): array
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();

        $this->assertNotNull($school, 'A school record is required.');

        App::instance('current_tenant', $school);

        return [$school, $this->adminUser($school)];
    }

    /**
     * The resource pages are permission-gated (module: inventory/hr,
     * permission: manage_* / view_employees), so tests act as the school's
     * Administrator (the Administrator role bypasses the permission checks).
     */
    private function adminUser(School $school): User
    {
        $adminRoleId = CustomRole::where('school_id', $school->id)
            ->where('name', 'Administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        return $admin ?? User::findOrFail(13);
    }

    public function test_inventory_items_list_renders_import_and_export_actions(): void
    {
        [, $user] = $this->tenantUser();
        $this->actingAs($user);

        $component = Livewire::test(ListInventoryItems::class);
        $component->assertOk();
        $component->assertSee('Import Inventory Items (CSV)', false);
        $component->assertSee('Export All', false);
    }

    public function test_fixed_assets_list_renders_import_and_export_actions(): void
    {
        [, $user] = $this->tenantUser();
        $this->actingAs($user);

        $component = Livewire::test(ListFixedAssets::class);
        $component->assertOk();
        $component->assertSee('Import Fixed Assets (CSV)', false);
        $component->assertSee('Export All', false);
    }

    public function test_suppliers_list_renders_import_and_export_actions(): void
    {
        [, $user] = $this->tenantUser();
        $this->actingAs($user);

        $component = Livewire::test(ListSuppliers::class);
        $component->assertOk();
        $component->assertSee('Import Suppliers (CSV)', false);
        $component->assertSee('Export All', false);
    }

    public function test_employees_list_renders_export_actions(): void
    {
        [$school, $user] = $this->tenantUser();
        $this->actingAs($user);

        // Defensive cleanup of any fixture leftover from a previous run
        // (the employee ->deleted hook soft-deletes its linked user).
        Employee::where('school_id', $school->id)->where('email', 'card.employee@schoolcore.test')->get()
            ->each->forceDelete();
        User::where('school_id', $school->id)
            ->where('email', 'card.employee@schoolcore.test')
            ->withTrashed()
            ->get()
            ->each->forceDelete();

        $employee = Employee::create([
            'school_id' => $school->id,
            'first_name' => 'Card',
            'last_name' => 'Employee',
            'national_id' => '99-1234567A00',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'phone_number' => '+263770000001',
            'email' => 'card.employee@schoolcore.test',
            'physical_address' => 'Test Address',
            'emergency_contact_name' => 'Test Contact',
            'emergency_contact_phone' => '+263770000002',
            'department' => 'Academics',
            'designation' => 'Maths Teacher',
            'role' => 'Teacher',
            'employment_type' => 'Permanent',
            'date_joined' => now()->toDateString(),
            'status' => 'active',
        ]);

        $component = Livewire::test(ListEmployees::class);
        $component->assertOk();
        $component->assertSee('Export All', false);
        $component->assertSee('Card Employee', false);
        $component->assertSee('Maths Teacher', false);

        $view = Livewire::test(ViewEmployee::class, ['record' => $employee->id]);
        $view->assertOk();
        $view->assertSee('Card Employee', false);
        $view->assertSee('EMP-', false);
        $view->assertSee('Employment Snapshot', false);

        $employee->forceDelete();
        User::where('school_id', $school->id)
            ->where('email', 'card.employee@schoolcore.test')
            ->withTrashed()
            ->get()
            ->each->forceDelete();
    }
}
