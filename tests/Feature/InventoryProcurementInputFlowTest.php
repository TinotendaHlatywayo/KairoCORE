<?php

namespace Tests\Feature;

use App\Filament\App\Resources\FixedAssetResource\Pages\CreateFixedAsset;
use App\Filament\App\Resources\FixedAssetResource\Pages\ListFixedAssets;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;
use Modules\Inventory\Models\DepreciationMethod;
use Modules\Inventory\Models\DepreciationSchedule;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Services\DepreciationEngine;
use Tests\TestCase;

/**
 * Covers the "smooth flow" fixes to Inventory & Procurement:
 * fixed assets that stand on their own, optional depreciation inputs with
 * user-extensible lists, an estimated cost on catalog items, and the
 * requisition line that fills itself in from the catalog.
 */
class InventoryProcurementInputFlowTest extends TestCase
{
    private ?School $school = null;

    private ?User $user = null;

    private array $created = [];

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

        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();
        $this->assertNotNull($school, 'A school fixture is required.');

        $this->school = $school;
        $this->actingAsTenant($school);
        App::instance('current_tenant', $school);

        $this->user = $this->adminUser($school);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        // Delete newest-first so dependents go before the rows they point at.
        foreach (array_reverse($this->created) as $record) {
            try {
                if ($record instanceof \Closure) {
                    $record();

                    continue;
                }

                $record->delete();
            } catch (\Throwable $e) {
                // Cleanup is best-effort: a row a test could not create must
                // never turn into a red test, but it must be reported loudly.
                fwrite(STDERR, "\n[cleanup] could not delete ".get_debug_type($record).': '.$e->getMessage()."\n");
            }
        }

        parent::tearDown();
    }

    /**
     * Resource pages are permission-gated, so act as the school's Administrator
     * (that role bypasses the permission checks), same as the other render tests.
     */
    private function adminUser(School $school): User
    {
        $adminRoleId = CustomRole::where('school_id', $school->id)
            ->where('name', 'Administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        if ($admin) {
            $admin->forceFill(['account_status' => 'active'])->save();

            return $admin;
        }

        $user = User::where('school_id', $school->id)->first();

        if (! $user) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => 'Admin',
                'email' => 'admin@rujeko.test',
                'password' => Hash::make('Password@1'),
                'account_status' => 'active',
                'requested_role' => 'administrator',
            ]);
        }

        $user->forceFill(['account_status' => 'active'])->save();

        return $user;
    }

    /**
     * Register rows created by a test so tearDown can remove them. Accepts
     * Eloquent models or a plain closure for rows inserted via the query
     * builder. Variadic on purpose: spreading a collection must track every
     * record, not just the first.
     */
    private function track(...$records): void
    {
        foreach ($records as $record) {
            $this->created[] = $record;
        }
    }

    private function seedSupplier(): int
    {
        $id = DB::table('inventory_suppliers')->insertGetId([
            'school_id' => $this->school->id,
            'name' => 'Flow Supplier '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->track(fn () => DB::table('inventory_suppliers')->where('id', $id)->delete());

        return $id;
    }

    private function seedItem(string $name, float $cost = 0, ?string $description = null): InventoryItem
    {
        $category = InventoryItem::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->orderBy('id')
            ->value('category_id');

        if (! $category) {
            $newCategory = InventoryCategory::create([
                'school_id' => $this->school->id,
                'name' => 'Flow Test Category',
            ]);
            $this->track($newCategory);
            $category = $newCategory->id;
        }

        $item = InventoryItem::create([
            'school_id' => $this->school->id,
            'category_id' => $category,
            'sku' => 'FLOW-'.strtoupper(uniqid()),
            'name' => $name,
            'description' => $description,
            'item_type' => 'consumable',
            'unit_of_measure' => 'pieces',
            'reorder_level' => 5,
            'current_quantity' => 0,
            'average_unit_cost' => $cost,
        ]);
        $this->track($item);

        return $item;
    }

    public function test_fixed_asset_saves_without_an_inventory_item(): void
    {
        Livewire::test(CreateFixedAsset::class)
            ->fillForm([
                'asset_number' => 'SC-2026-FA-FLOW01',
                'asset_name' => 'Epson EcoTank Printer',
                'description' => 'Colour ink tank printer shared by the admin block.',
                'serial_number' => 'EPS-9911',
                'acquisition_date' => now()->toDateString(),
                'purchase_cost' => 450.00,
                'salvage_value' => 45.00,
                // Useful life deliberately left blank, and no linked catalog item.
                'useful_life_years' => null,
                'depreciation_method' => 'straight_line',
                'assigned_location_id' => null,
                'custodian_id' => null,
                'funding_source' => 'school_funds',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $asset = FixedAsset::withoutTenantScope()
            ->where('asset_number', 'SC-2026-FA-FLOW01')
            ->firstOrFail();

        $this->track($asset);

        $this->assertSame('Epson EcoTank Printer', $asset->asset_name);
        $this->assertSame('Colour ink tank printer shared by the admin block.', $asset->description);
        $this->assertNull($asset->inventory_item_id, 'A fixed asset must not require a catalog item.');
        $this->assertNull($asset->useful_life_years, 'Useful life is optional and may be left blank.');
        $this->assertEquals(450.00, (float) $asset->current_value, 'Current value falls back to the purchase cost.');
    }

    public function test_depreciation_is_not_posted_for_a_method_with_no_formula(): void
    {
        $engine = app(DepreciationEngine::class);

        $this->assertTrue($engine->supportsMethod('straight_line'));
        $this->assertTrue($engine->supportsMethod('double_declining'));
        $this->assertFalse($engine->supportsMethod('sum_of_years_digits'));

        $asset = FixedAsset::create([
            'school_id' => $this->school->id,
            'asset_name' => 'Projector Without Formula',
            'asset_number' => 'SC-2026-FA-FLOW02',
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 900.00,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'sum_of_years_digits',
            'current_value' => 900.00,
            'status' => 'active',
        ]);
        $this->track($asset);

        // Posting must be refused with a clear reason, not a silent no-op or 500.
        $this->expectException(\InvalidArgumentException::class);
        $engine->postScheduleToLedger($asset);
    }

    public function test_schools_can_add_their_own_depreciation_method(): void
    {
        $method = DepreciationMethod::createForSchool($this->school->id, 'Sum of the Years Digits');
        $this->track($method);

        $this->assertSame('sum-of-the-years-digits', $method->key);
        $this->assertFalse($method->is_system);

        $options = DepreciationMethod::optionsForSchool($this->school->id);

        $this->assertArrayHasKey('straight_line', $options, 'System methods must always be offered.');
        $this->assertArrayHasKey('double_declining', $options);
        $this->assertArrayHasKey('sum-of-the-years-digits', $options);
    }

    public function test_system_methods_are_not_duplicated_for_a_school(): void
    {
        DepreciationMethod::optionsForSchool($this->school->id);
        DepreciationMethod::optionsForSchool($this->school->id);

        $count = DepreciationMethod::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('key', 'straight_line')
            ->count();

        $this->assertSame(1, $count);

        $system = DepreciationMethod::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('is_system', true)
            ->get();
        $this->track(...$system);
    }

    public function test_selecting_a_catalog_item_fills_the_requisition_line(): void
    {
        $item = $this->seedItem('Chlorine Tablets 5g', 45.50);

        $values = ProcurementRequestResource::requisitionLineValuesForCatalogItem($item->id);

        $this->assertSame($item->name, $values['item_name']);
        $this->assertEquals(45.50, (float) $values['estimated_unit_cost']);
    }

    public function test_selecting_a_catalog_item_without_a_cost_keeps_the_typed_cost(): void
    {
        $item = $this->seedItem('Unpriced Spare Part', 0.0);

        $values = ProcurementRequestResource::requisitionLineValuesForCatalogItem($item->id);

        $this->assertSame($item->name, $values['item_name']);
        $this->assertArrayNotHasKey(
            'estimated_unit_cost',
            $values,
            'A zero estimate must not produce a value that wipes a cost the user already typed.'
        );
    }

    public function test_clearing_the_catalog_item_leaves_the_line_alone(): void
    {
        $this->assertSame([], ProcurementRequestResource::requisitionLineValuesForCatalogItem(null));
        $this->assertSame([], ProcurementRequestResource::requisitionLineValuesForCatalogItem(0));
    }

    public function test_requisition_description_reaches_an_item_that_has_none(): void
    {
        $item = $this->seedItem('Water Filter Cartridge', 30.00);

        $request = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-FLOW-0003',
            'requester_id' => $this->user->id,
            'urgency' => 'medium',
            'purpose' => 'Replace filter cartridges.',
        ]);
        $this->track($request);

        $line = $request->items()->create([
            'item_name' => $item->name,
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'estimated_unit_cost' => 30.00,
            'specifications' => '10 micron, 10 inch cartridge, fits borehole housing.',
        ]);

        $supplierId = $this->seedSupplier();

        ProcurementRequestResource::approveRequest($request->fresh(), $supplierId);

        $item->refresh();
        $this->assertSame(
            '10 micron, 10 inch cartridge, fits borehole housing.',
            $item->description,
            'A requisition description must land on the linked catalog item.'
        );

        $line->refresh();
        $this->assertSame(2, (int) $line->quantity);
    }

    public function test_requisition_description_never_overwrites_an_existing_item_description(): void
    {
        $item = $this->seedItem('Borehole Pump', 900.00, 'Original catalog description, do not clobber.');

        $request = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-FLOW-0004',
            'requester_id' => $this->user->id,
            'urgency' => 'medium',
            'purpose' => 'Replace failed pump.',
        ]);
        $this->track($request);

        $request->items()->create([
            'item_name' => $item->name,
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'estimated_unit_cost' => 900.00,
            'specifications' => 'Requisitioner wording that should not win.',
        ]);

        $supplierId = $this->seedSupplier();

        ProcurementRequestResource::approveRequest($request->fresh(), $supplierId);

        $item->refresh();
        $this->assertSame('Original catalog description, do not clobber.', $item->description);
    }

    public function test_approving_a_fixed_asset_requisition_registers_a_named_asset(): void
    {
        // Unique per run: the approval path reuses a catalog item whose name
        // already matches, which would skip provisioning the asset entirely.
        $assetName = 'Epson HD Projector '.uniqid();

        $request = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-FLOW-0005',
            'requester_id' => $this->user->id,
            'urgency' => 'high',
            'purpose' => 'New projector for the hall.',
        ]);
        $this->track($request);

        $request->items()->create([
            'item_name' => $assetName,
            'quantity' => 1,
            'estimated_unit_cost' => 1200.00,
            'is_fixed_asset' => true,
            'specifications' => 'WUXGA, 4000 lumens, HDMI.',
        ]);

        $supplierId = $this->seedSupplier();
        ProcurementRequestResource::approveRequest($request->fresh(), $supplierId);

        $asset = FixedAsset::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('asset_name', $assetName)
            ->first();

        if ($asset) {
            $this->track($asset);
        }

        $this->assertNotNull($asset, 'The capitalized requisition must register a named fixed asset.');
        $this->assertSame('WUXGA, 4000 lumens, HDMI.', $asset->description);
        $this->assertEquals(1200.00, (float) $asset->purchase_cost);

        // Clean up the catalog item the approval auto-provisioned.
        $provisioned = InventoryItem::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('name', $assetName)
            ->first();
        if ($provisioned) {
            $this->track($provisioned);
        }
    }

    public function test_posting_depreciation_warns_instead_of_failing_for_an_unsupported_method(): void
    {
        $method = DepreciationMethod::createForSchool($this->school->id, 'Reducing Balance '.uniqid());
        $this->track($method);

        $asset = FixedAsset::create([
            'school_id' => $this->school->id,
            'asset_name' => 'Solar Inverter',
            'asset_number' => 'SC-2026-FA-FLOW07',
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 3000.00,
            'salvage_value' => 0,
            'useful_life_years' => 10,
            'depreciation_method' => $method->key,
            'current_value' => 3000.00,
            'status' => 'active',
        ]);
        $this->track($asset);

        Livewire::test(ListFixedAssets::class)
            ->callTableAction('Post Depreciation', $asset)
            ->assertNotified();

        // Scoped to this asset, not the table, so pre-existing rows elsewhere
        // in the dev database cannot mask a real regression.
        $this->assertSame(
            0,
            DepreciationSchedule::withoutTenantScope()->where('fixed_asset_id', $asset->id)->count(),
        );
    }

    public function test_posting_depreciation_warns_when_useful_life_is_blank(): void
    {
        $asset = FixedAsset::create([
            'school_id' => $this->school->id,
            'asset_name' => 'Unamortised Asset',
            'asset_number' => 'SC-2026-FA-FLOW08',
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 750.00,
            'salvage_value' => 0,
            'useful_life_years' => null,
            'depreciation_method' => 'straight_line',
            'current_value' => 750.00,
            'status' => 'active',
        ]);
        $this->track($asset);

        Livewire::test(ListFixedAssets::class)
            ->callTableAction('Post Depreciation', $asset)
            ->assertNotified();

        // Scoped to this asset, not the table, so pre-existing rows elsewhere
        // in the dev database cannot mask a real regression.
        $this->assertSame(
            0,
            DepreciationSchedule::withoutTenantScope()->where('fixed_asset_id', $asset->id)->count(),
        );
    }

    public function test_posting_depreciation_still_works_for_a_supported_method(): void
    {
        $asset = FixedAsset::create([
            'school_id' => $this->school->id,
            'asset_name' => 'Office Chair',
            'asset_number' => 'SC-2026-FA-FLOW09',
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 1000.00,
            'salvage_value' => 100.00,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'current_value' => 1000.00,
            'status' => 'active',
        ]);
        $this->track($asset);

        Livewire::test(ListFixedAssets::class)
            ->callTableAction('Post Depreciation', $asset)
            ->assertNotified();

        $this->assertDatabaseHas('depreciation_schedules', [
            'fixed_asset_id' => $asset->id,
        ]);

        $schedules = DepreciationSchedule::withoutTenantScope()
            ->where('fixed_asset_id', $asset->id)
            ->get();
        $this->track(...$schedules);

        $this->assertCount(5, $schedules);
    }

    public function test_funding_source_and_custodian_can_be_added_from_the_asset_form(): void
    {
        Livewire::test(CreateFixedAsset::class)
            ->fillForm([
                'asset_number' => 'SC-2026-FA-FLOW10',
                'asset_name' => 'Staff Notice Board',
                'acquisition_date' => now()->toDateString(),
                'purchase_cost' => 120.00,
                'depreciation_method' => 'straight_line',
                'funding_source' => 'ngo_sponsorship',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $asset = FixedAsset::withoutTenantScope()
            ->where('asset_number', 'SC-2026-FA-FLOW10')
            ->firstOrFail();
        $this->track($asset);

        $this->assertSame('ngo_sponsorship', $asset->funding_source, 'A funding source typed into the + must persist.');
    }

    public function test_the_asset_form_offers_a_create_option_on_all_three_stewardship_fields(): void
    {
        $selects = $this->assetFormSelects();

        foreach (['assigned_location_id', 'custodian_id', 'funding_source', 'depreciation_method'] as $field) {
            $this->assertArrayHasKey($field, $selects, "The {$field} select must exist on the asset form.");
            $this->assertTrue(
                $selects[$field]->hasCreateOptionActionFormSchema(),
                "The {$field} field must show a + to add a value that is not listed."
            );
            $this->assertNotNull(
                $selects[$field]->getCreateOptionUsing(),
                "The {$field} create option must know how to persist a new value."
            );
        }
    }

    public function test_creating_a_location_from_the_asset_form_assigns_it_to_the_current_school(): void
    {
        $selects = $this->assetFormSelects();

        $locationId = $this->runCreateOption($selects['assigned_location_id'], [
            'name' => 'Computer Lab '.uniqid(),
            'code' => 'LAB-'.uniqid(),
            'type' => 'ict',
        ]);

        $location = InventoryLocation::withoutTenantScope()->findOrFail($locationId);
        $this->track($location);

        $this->assertSame(
            $this->school->id,
            (int) $location->school_id,
            'A location added from the asset form must belong to the acting school, not bleed across tenants.'
        );
    }

    public function test_creating_a_custodian_from_the_asset_form_assigns_it_to_the_current_school(): void
    {
        $selects = $this->assetFormSelects();

        $userId = $this->runCreateOption($selects['custodian_id'], [
            'name' => 'Steward '.uniqid(),
            'email' => '',
        ]);

        $user = User::withoutGlobalScopes()->findOrFail($userId);
        // Users soft-delete by default; a test fixture should not leave a
        // soft-deleted row behind on every run.
        $this->track(fn () => $user->forceDelete());

        $this->assertSame($this->school->id, (int) $user->school_id);
        $this->assertNotEmpty($user->email, 'A generated email is required because email is unique.');
    }

    public function test_creating_a_depreciation_method_from_the_asset_form_assigns_it_to_the_current_school(): void
    {
        $selects = $this->assetFormSelects();
        $name = 'Sum of the Years Digits '.uniqid();

        $key = $this->runCreateOption($selects['depreciation_method'], ['name' => $name]);

        $method = DepreciationMethod::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('key', $key)
            ->firstOrFail();
        $this->track($method);

        $this->assertSame($name, $method->name);
        $this->assertFalse($method->is_system, 'A user-added method is not a system method.');
    }

    /**
     * Invoke a create-option callback exactly as Filament's built-in
     * "createOption" action does, so the test exercises the real path
     * (including Filament's named data/form injection).
     */
    private function runCreateOption(Select $select, array $data): mixed
    {
        $page = Livewire::test(CreateFixedAsset::class)->instance();

        return $select->evaluate($select->getCreateOptionUsing(), [
            'data' => $data,
            'form' => $page->getForm('form'),
        ]);
    }

    /**
     * @return array<string, Select>
     */
    private function assetFormSelects(): array
    {
        $page = Livewire::test(CreateFixedAsset::class)->instance();

        return $this->collectSelects($page->getForm('form')->getComponents());
    }

    /**
     * @param  array<Component>  $components
     * @return array<string, Select>
     */
    private function collectSelects(array $components): array
    {
        $found = [];

        foreach ($components as $component) {
            if ($component instanceof Select) {
                $found[(string) $component->getName()] = $component;
            }

            $children = method_exists($component, 'getChildComponents')
                ? $component->getChildComponents()
                : [];

            $found += $this->collectSelects($children);
        }

        return $found;
    }

    public function test_fixed_asset_list_shows_the_asset_name(): void
    {
        $asset = FixedAsset::create([
            'school_id' => $this->school->id,
            'asset_name' => 'Canon imageRUNNER C3226i',
            'asset_number' => 'SC-2026-FA-FLOW06',
            'acquisition_date' => now()->toDateString(),
            'purchase_cost' => 2100.00,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'current_value' => 2100.00,
            'status' => 'active',
        ]);
        $this->track($asset);

        Livewire::test(ListFixedAssets::class)
            ->assertOk()
            ->assertSee('Canon imageRUNNER C3226i', false);
    }
}
