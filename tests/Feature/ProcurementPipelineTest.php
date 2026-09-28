<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages\CreateGoodsReceivedNote;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages\EditGoodsReceivedNote;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages\ListProcurementRequests;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages\ViewPurchaseOrder;
use Modules\Inventory\Models\GoodsReceivedItem;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementOrderItem;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Models\ProcurementRequestItem;
use Modules\Inventory\Services\ProcurementPipelineService;
use Tests\TestCase;

class ProcurementPipelineTest extends TestCase
{
    private ?School $school = null;

    private ?User $user = null;

    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysql();

        $school = School::where('subdomain', 'rujeko')->first();
        if (! $school) {
            $school = School::create(['name' => 'Rujeko High', 'subdomain' => 'rujeko', 'status' => 'active']);
        }
        $this->school = $school;
        $this->actingAsTenant($school);

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
        $this->user = $user;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        // The receiving pipeline writes child rows that are not models we hold
        // references to. They all belong to this test's own fixture school, so
        // clearing them by school keeps the shared dev database clean.
        foreach (['goods_received_items', 'inventory_stock_movements', 'inventory_batches', 'fixed_assets'] as $table) {
            try {
                DB::table($table)->where('school_id', $this->school->id)->delete();
            } catch (\Throwable $e) {
            }
        }

        // Clean up data created for test purposes only.
        foreach ($this->created as $record) {
            try {
                $record->delete();
            } catch (\Throwable $e) {
            }
        }

        // Restore any pre-existing general warehouses we stacked for the
        // no-warehouse scenarios so the fixture school is untouched.
        foreach ($this->stackedLocations as $row) {
            try {
                DB::table('inventory_locations')->insert($row);
            } catch (\Throwable $e) {
            }
        }

        parent::tearDown();
    }

    private array $stackedLocations = [];

    /**
     * Temporarily remove every general warehouse for the school so the service
     * is forced down the auto-provision path. The removed rows are restored in
     * tearDown().
     */
    private function stashGeneralWarehouses(): void
    {
        $this->stackedLocations = DB::table('inventory_locations')
            ->where('school_id', $this->school->id)
            ->where('type', 'general')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        DB::table('inventory_locations')
            ->where('school_id', $this->school->id)
            ->where('type', 'general')
            ->delete();
    }

    private function useMysql(): void
    {
        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function seedSupplier(): int
    {
        $id = DB::table('inventory_suppliers')->insertGetId([
            'school_id' => $this->school->id,
            'name' => 'Test Supplier '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->created[] = (object) ['delete' => fn () => DB::table('inventory_suppliers')->where('id', $id)->delete()];

        return $id;
    }

    private function seedCategoryAndItem(): array
    {
        $category = InventoryCategory::create([
            'school_id' => $this->school->id,
            'name' => 'Test Category',
        ]);
        $this->created[] = $category;

        $item = InventoryItem::create([
            'school_id' => $this->school->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Laptop',
            'item_type' => 'consumable',
            'unit_of_measure' => 'pieces',
            'reorder_level' => 1,
            'current_quantity' => 0,
            'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $item;

        return [$category, $item];
    }

    private function seedBankAccount(float $balance = 1000): SchoolBankAccount
    {
        $account = SchoolBankAccount::create([
            'school_id' => $this->school->id,
            'bank_name' => 'Test Bank',
            'account_name' => 'School Main',
            'account_number' => 'ACCT-'.uniqid(),
            'is_default' => true,
            'is_active' => true,
            'balance' => $balance,
        ]);
        $this->created[] = $account;

        return $account;
    }

    private function seedOrder(array $items, string $status = 'draft'): ProcurementOrder
    {
        $supplierId = $this->seedSupplier();

        $order = ProcurementOrder::create([
            'school_id' => $this->school->id,
            'supplier_id' => $supplierId,
            'order_number' => 'PO-TEST-'.uniqid(),
            'order_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => collect($items)->sum(fn ($i) => $i['qty'] * $i['cost']),
        ]);
        $this->created[] = $order;

        foreach ($items as $item) {
            $orderItem = ProcurementOrderItem::create([
                'procurement_order_id' => $order->id,
                'inventory_item_id' => $item['item_id'],
                'quantity_ordered' => $item['qty'],
                'quantity_received' => $item['received'] ?? 0,
                'unit_cost' => $item['cost'],
            ]);
            $this->created[] = $orderItem;
        }

        return $order;
    }

    public function test_grn_receive_goods_autoprovisions_warehouse_when_missing(): void
    {
        // School has no inventory_locations of type general => old code threw a
        // RuntimeException (the reported 500 on /workspace/goods-receiveds/create).
        $this->stashGeneralWarehouses();

        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]]);

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'grn_number' => 'GRN-TEST-'.uniqid(),
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = GoodsReceivedItem::create([
            'goods_received_note_id' => $grn->id,
            'inventory_item_id' => $item->id,
            'quantity_accepted' => 5,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $location = DB::table('inventory_locations')
            ->where('school_id', $this->school->id)
            ->where('type', 'general')
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($location);
        $this->created[] = (object) ['delete' => fn () => DB::table('inventory_locations')->where('id', $location->id)->delete()];

        $this->assertDatabaseHas('inventory_locations', [
            'school_id' => $this->school->id,
            'type' => 'general',
        ]);

        $order->refresh();
        $this->assertSame(5, $order->items()->first()->quantity_received);
        $this->assertSame('completed', $order->status);

        // The catalog item's quantity on hand must reflect what was received.
        $item->refresh();
        $this->assertSame(5, (int) $item->current_quantity);
        $this->assertEquals(100.0, (float) $item->average_unit_cost);
    }

    public function test_approve_order_deducts_balance_and_records_paid_expense(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 7, 'cost' => 100]]);
        $bank = $this->seedBankAccount(1000);

        app(ProcurementPipelineService::class)->approveOrder($order, $bank->id);

        $order->refresh();
        $this->assertSame('approved', $order->status);
        $this->assertSame($bank->id, $order->bank_account_id);

        $bank->refresh();
        $this->assertEquals(300.0, (float) $bank->balance);

        $expense = Expense::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('reference_number', 'EXP-PO-'.$order->order_number)
            ->first();
        $this->assertNotNull($expense);
        $this->assertSame((float) 700, (float) $expense->amount);
        $this->assertSame('paid', $expense->status);
        $this->assertSame($bank->id, $expense->bank_account_id);
    }

    public function test_recompute_order_total_after_unit_cost_edit(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 7, 'cost' => 100]]);
        $this->assertSame((float) 700, (float) $order->total_amount);

        // Simulate the user editing unit cost 100 -> 50 in the edit page.
        $order->items()->first()->update(['unit_cost' => 50]);

        app(ProcurementPipelineService::class)->recomputeOrderTotal($order);

        $order->refresh();
        $this->assertSame((float) 350, (float) $order->total_amount);
    }

    public function test_refund_missing_items_credits_bank_and_offsets_expense(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        // 7 laptops @100, but only 5 received => 2 missing => $200 refundable.
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 7, 'cost' => 100, 'received' => 5]]);
        $bank = $this->seedBankAccount(1000);

        $refund = app(ProcurementPipelineService::class)->refundOrder($order, $bank->id);

        $this->assertSame((float) 200, $refund);

        $bank->refresh();
        $this->assertSame(1200.0, (float) $bank->balance);

        $expense = Expense::withoutTenantScope()
            ->where('school_id', $this->school->id)
            ->where('reference_number', 'REF-PO-'.$order->order_number)
            ->first();
        $this->assertNotNull($expense);
        $this->assertSame((float) -200, (float) $expense->amount);

        // A second refund attempt yields nothing: already fully refunded.
        $second = app(ProcurementPipelineService::class)->refundOrder($order, $bank->id);
        $this->assertSame(0.0, $second);
    }

    public function test_supplier_quick_create_row_via_repository_scope(): void
    {
        $schoolId = $this->school->id;
        $createdId = DB::table('inventory_suppliers')->insertGetId([
            'school_id' => $schoolId,
            'name' => 'Quick Supplier '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->created[] = (object) ['delete' => fn () => DB::table('inventory_suppliers')->where('id', $createdId)->delete()];

        $this->assertDatabaseHas('inventory_suppliers', ['id' => $createdId, 'school_id' => $schoolId]);
    }

    public function test_grn_create_page_renders_without_500_when_no_warehouse(): void
    {
        // Simulate the reported 500: a school with no general warehouse and no
        // purchase orders should still be able to open the create GRN page.
        $this->stashGeneralWarehouses();

        $component = Livewire::test(CreateGoodsReceivedNote::class);
        $component->assertOk();
    }

    public function test_view_purchase_order_page_renders_with_approve_and_refund_actions(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100, 'received' => 2]]);
        $bank = $this->seedBankAccount(1000);

        App::instance('current_tenant', $this->school);

        $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()]);
        $component->assertOk();
        $component->assertSee('Refund Missing Items', false);
    }

    public function test_approve_action_on_view_page_calls_service(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]]);
        $bank = $this->seedBankAccount(1000);

        App::instance('current_tenant', $this->school);

        $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()]);
        $component->callAction('approve', ['bank_account_id' => $bank->id]);

        $component->assertHasNoErrors();

        $this->assertSame(500.0, (float) $bank->refresh()->balance);
        $this->assertSame('approved', $order->refresh()->status);
    }

    public function test_refund_action_on_view_page_calls_service(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        // 7 laptops @100, 5 received => 2 missing => $200 refundable.
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 7, 'cost' => 100, 'received' => 5]]);
        $bank = $this->seedBankAccount(1000);

        App::instance('current_tenant', $this->school);

        $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()]);
        $component->callAction('refund_missing', ['bank_account_id' => $bank->id]);

        $component->assertHasNoErrors();

        $this->assertSame(1200.0, (float) $bank->refresh()->balance);
        $this->assertSame((float) 200, (float) $order->refresh()->refunded_amount);
    }

    public function test_create_form_autofills_from_linked_request_number(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $supplierId = $this->seedSupplier();

        $request = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-AUTO-'.uniqid(),
            'requester_id' => $this->user->id,
            'status' => 'draft',
            'urgency' => 'medium',
        ]);
        $this->created[] = $request;

        $this->created[] = ProcurementRequestItem::create([
            'procurement_request_id' => $request->id,
            'item_name' => 'Test Laptop',
            'inventory_item_id' => $item->id,
            'quantity' => 3,
            'estimated_unit_cost' => 150.00,
            'is_fixed_asset' => true,
        ]);

        $linked = ProcurementOrder::create([
            'school_id' => $this->school->id,
            'procurement_request_id' => $request->id,
            'supplier_id' => $supplierId,
            'order_number' => 'PO-LINK-'.uniqid(),
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => 450,
        ]);
        $this->created[] = $linked;

        $component = Livewire::test(CreatePurchaseOrder::class);
        $component->set('data.procurement_request_id', $request->id);

        $state = $component->get('data');

        $this->assertSame((int) $request->id, (int) $state['procurement_request_id']);
        $this->assertSame($supplierId, (int) $state['supplier_id']);
        $this->assertSame('3', (string) $state['items'][0]['quantity_ordered']);
        $this->assertSame('150', (string) $state['items'][0]['unit_cost']);
        $this->assertTrue((bool) $state['items'][0]['is_fixed_asset']);
        $this->assertSame((int) $item->id, (int) $state['items'][0]['inventory_item_id']);
    }

    public function test_bulk_delete_requisitions_skips_those_with_approved_purchase_orders(): void
    {
        $request1 = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-BULK1-'.uniqid(),
            'requester_id' => $this->user->id,
            'status' => 'draft',
            'urgency' => 'low',
        ]);
        $this->created[] = $request1;

        [, $item] = $this->seedCategoryAndItem();

        $request2 = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-BULK2-'.uniqid(),
            'requester_id' => $this->user->id,
            'status' => 'approved',
            'urgency' => 'medium',
        ]);
        $this->created[] = $request2;

        $this->created[] = ProcurementRequestItem::create([
            'procurement_request_id' => $request2->id,
            'item_name' => 'Test Laptop',
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'estimated_unit_cost' => 100.00,
        ]);

        $linked = ProcurementOrder::create([
            'school_id' => $this->school->id,
            'procurement_request_id' => $request2->id,
            'supplier_id' => $this->seedSupplier(),
            'order_number' => 'PO-BULK-'.uniqid(),
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 100,
        ]);
        $this->created[] = $linked;

        $component = Livewire::test(ListProcurementRequests::class);
        $component->callTableBulkAction('delete', [$request1->id, $request2->id]);

        $this->assertDatabaseMissing('procurement_requests', ['id' => $request1->id]);
        $this->assertDatabaseHas('procurement_requests', ['id' => $request2->id]);
    }

    public function test_edit_page_loads_existing_order_items(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]]);

        $page = Livewire::test(EditPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful()
            ->instance();

        $repeater = $page->getForm('form')->getComponent('data.items');

        $this->assertCount(
            1,
            $repeater->getState(),
            'The order items repeater must be pre-filled with the existing lines, otherwise saving the page would delete them.'
        );
    }

    public function test_editing_line_costs_persists_the_recomputed_order_total(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]]);

        $this->assertEquals(500.00, (float) $order->total_amount);

        $page = Livewire::test(EditPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful()
            ->instance();

        $repeater = $page->getForm('form')->getComponent('data.items');
        $rows = $repeater->getState();

        $row = array_key_first($rows);
        $rows[$row]['unit_cost'] = '80';
        $rows[$row]['quantity_ordered'] = '4';

        Livewire::test(EditPurchaseOrder::class, ['record' => $order->getKey()])
            ->fillForm(['items' => $rows])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(
            320.00,
            (float) $order->fresh()->total_amount,
            'The order total must reflect the edited line, not values loaded before the edit.'
        );
    }

    public function test_approve_modal_summary_has_every_value_the_modal_renders(): void
    {
        // Regression: the Approve modal defined its read-only Order Summary
        // with ->default() on the action's own schema instead of supplying the
        // values through fillForm(), so every summary field opened blank.
        // fillForm() is what populates a mounted action form, and it is driven
        // by this array, so asserting the array pins the bug closed.
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 2, 'cost' => 150]]);

        $summary = PurchaseOrderResource::approvalSummary($order);

        $this->assertSame([
            'order_number' => $order->order_number,
            'supplier' => $order->supplier?->name,
            'order_date' => $order->order_date?->format('Y-m-d'),
            'total_amount' => '300.00',
            'item_count' => '1',
        ], $summary);

        $this->assertNotContains(
            '',
            array_values($summary),
            'No Order Summary field may render blank.'
        );
    }

    public function test_editing_a_unit_cost_is_reflected_on_the_view_page(): void
    {
        // Mirrors the reported flow: open a purchase order from the table, hit
        // Edit, change a unit cost, then go back to the order. The View page
        // renders the stored total_amount, so a stale figure there means the
        // recompute did not persist.
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 2, 'cost' => 150]]);
        $this->assertEquals(300.0, (float) $order->total_amount);

        Livewire::test(EditPurchaseOrder::class, ['record' => $order->getKey()])
            ->fillForm([
                'items' => [[
                    'inventory_item_id' => $item->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => 250.00,
                    'is_fixed_asset' => false,
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(
            500.0,
            (float) $order->refresh()->total_amount,
            'Saving a new unit cost must persist the recomputed order total.'
        );

        $view = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful();

        $this->assertStringContainsString(
            '500',
            $view->html(),
            'The order view must show the newly saved total, not the previous one.'
        );
    }

    public function test_order_form_shows_a_live_total_that_tracks_the_lines(): void
    {
        $this->assertEquals(320.00, PurchaseOrderResource::totalForLines([
            ['quantity_ordered' => '4', 'unit_cost' => '80.00'],
        ]));

        $this->assertEquals(
            0.0,
            PurchaseOrderResource::totalForLines(null),
            'A brand new order has no lines yet and must show zero, not an error.'
        );

        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 2, 'cost' => 150]]);

        $page = Livewire::test(EditPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful();

        $this->assertStringContainsString(
            '300.00',
            $page->html(),
            'The edit form must show the current order total so an edit is visible before saving.'
        );
    }

    public function test_grn_number_is_generated_by_the_system(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 2, 'cost' => 100]]);

        $first = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $first;

        $this->assertMatchesRegularExpression(
            '/^GRN-'.now()->year.'-\d{5}$/',
            $first->grn_number,
            'The GRN number must be issued by the system, never left blank.'
        );

        $second = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $second;

        $this->assertNotSame(
            $first->grn_number,
            $second->grn_number,
            'Consecutive notes must not share a GRN number.'
        );
    }

    public function test_receiving_against_an_order_loads_every_line_for_comparison(): void
    {
        [, $laptops] = $this->seedCategoryAndItem();

        $category = InventoryCategory::create(['school_id' => $this->school->id, 'name' => 'Second Cat '.uniqid()]);
        $this->created[] = $category;
        $projector = InventoryItem::create([
            'school_id' => $this->school->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Projector',
            'item_type' => 'consumable',
            'unit_of_measure' => 'pieces',
            'reorder_level' => 1,
            'current_quantity' => 0,
            'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $projector;

        $order = $this->seedOrder([
            ['item_id' => $laptops->id, 'qty' => 5, 'cost' => 100],
            ['item_id' => $projector->id, 'qty' => 2, 'cost' => 250],
        ], 'sent');

        // The whole order is loaded, with ordered vs already received vs
        // outstanding so the user can compare without leaving the page.
        $rows = GoodsReceivedNote::deliveryRows($order->load('items'));

        $this->assertCount(2, $rows);
        $this->assertSame([5, 0, 5, 5], [
            $rows[0]['quantity_ordered'],
            $rows[0]['quantity_received_before'],
            $rows[0]['quantity_outstanding'],
            $rows[0]['quantity_accepted'],
        ]);
        $this->assertSame([2, 0, 2, 2], [
            $rows[1]['quantity_ordered'],
            $rows[1]['quantity_received_before'],
            $rows[1]['quantity_outstanding'],
            $rows[1]['quantity_accepted'],
        ]);
    }

    public function test_receiving_against_an_order_excludes_what_was_already_received(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([
            ['item_id' => $item->id, 'qty' => 10, 'cost' => 100, 'received' => 4],
        ], 'partially_received');

        $rows = GoodsReceivedNote::deliveryRows($order->load('items'));

        $this->assertSame(10, $rows[0]['quantity_ordered']);
        $this->assertSame(4, $rows[0]['quantity_received_before']);
        $this->assertSame(6, $rows[0]['quantity_outstanding'], 'Only what is still outstanding may be received.');
        $this->assertSame(6, $rows[0]['quantity_accepted']);
    }

    public function test_create_grn_from_a_purchase_order_saves_without_validation_errors(): void
    {
        // The reported failure: opening Receive Goods raised
        // "The grn number field is required" and "The inventory item field is
        // required" because mount() filled only two keys and wiped the rest.
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]], 'sent');

        $component = Livewire::withQueryParams(['procurement_order_id' => $order->id])
            ->test(CreateGoodsReceivedNote::class)
            ->assertSuccessful()
            ->assertFormSet(function (array $state) use ($order, $item): array {
                $this->assertSame($order->id, $state['procurement_order_id']);
                $this->assertNotEmpty($state['grn_number'], 'The GRN number must be pre-filled for display.');
                // Filament keys repeater state by generated UUID, not index.
                $items = array_values($state['items']);
                $this->assertCount(1, $items);
                $this->assertSame($item->id, $items[0]['inventory_item_id']);
                $this->assertSame(5, $items[0]['quantity_ordered']);
                $this->assertSame(0, $items[0]['quantity_received_before']);
                $this->assertSame(5, $items[0]['quantity_outstanding']);
                $this->assertSame(5, $items[0]['quantity_accepted']);

                return [];
            });

        $component->call('create')->assertHasNoFormErrors();

        $grn = GoodsReceivedNote::where('procurement_order_id', $order->id)
            ->orderByDesc('id')
            ->first();
        $this->created[] = $grn;
        foreach ($grn->items as $line) {
            $this->created[] = $line;
        }

        $this->assertNotEmpty($grn->grn_number);
        $this->assertSame(5, (int) $item->refresh()->current_quantity);
        $this->assertSame(5, (int) $order->refresh()->items()->first()->quantity_received);
    }

    public function test_partial_receipt_records_only_what_arrived_and_keeps_the_order_open(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 5, 'cost' => 100]], 'sent');

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = GoodsReceivedItem::create([
            'goods_received_note_id' => $grn->id,
            'inventory_item_id' => $item->id,
            'quantity_accepted' => 2,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $this->assertSame(2, (int) $item->refresh()->current_quantity, 'Only what arrived goes into stock.');
        $this->assertSame(2, (int) $order->refresh()->items()->first()->quantity_received);
        $this->assertSame('partially_received', $order->refresh()->status);

        // Outstanding against the order as a whole, once this delivery is counted.
        $rows = GoodsReceivedNote::deliveryRows($order->refresh()->load('items'));
        $this->assertSame(2, $rows[0]['quantity_received_before']);
        $this->assertSame(3, $rows[0]['quantity_outstanding'], 'The undelivered balance stays outstanding.');

        // While editing that same note its own quantity is excluded, so the
        // line still shows the full 5 as the maximum it can hold.
        $editable = GoodsReceivedNote::deliveryRows($order->refresh()->load('items'), $grn->refresh());
        $this->assertSame(0, $editable[0]['quantity_received_before']);
        $this->assertSame(5, $editable[0]['quantity_outstanding']);
        $this->assertSame(2, $editable[0]['quantity_accepted'], 'The existing quantity is preserved on edit.');
    }

    public function test_a_line_left_at_zero_records_nothing(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 4, 'cost' => 100]], 'sent');

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = GoodsReceivedItem::create([
            'goods_received_note_id' => $grn->id,
            'inventory_item_id' => $item->id,
            'quantity_accepted' => 0,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $this->assertSame(0, (int) $item->refresh()->current_quantity, 'A line not delivered must not touch stock.');
        $this->assertSame(0, (int) $order->refresh()->items()->first()->quantity_received);
        $this->assertDatabaseMissing('inventory_stock_movements', [
            'inventory_item_id' => $item->id,
            'quantity' => 0,
        ]);
    }

    public function test_receiving_more_than_outstanding_is_rejected(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([
            ['item_id' => $item->id, 'qty' => 3, 'cost' => 100, 'received' => 2],
        ], 'partially_received');

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = GoodsReceivedItem::create([
            'goods_received_note_id' => $grn->id,
            'inventory_item_id' => $item->id,
            'quantity_accepted' => 9,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        $this->expectException(\RuntimeException::class);

        try {
            app(ProcurementPipelineService::class)->receiveGoods($grn);
        } finally {
            $this->assertSame(2, (int) $order->refresh()->items()->first()->quantity_received, 'Over-receipt must roll back.');
            $this->assertSame(0, (int) $item->refresh()->current_quantity);
        }
    }

    public function test_receiving_a_fixed_asset_line_registers_one_asset_per_unit(): void
    {
        // "The quantity received are the quantities recorded in the system in
        // either inventory items or assets."
        $category = InventoryCategory::create(['school_id' => $this->school->id, 'name' => 'Assets '.uniqid()]);
        $this->created[] = $category;

        $laptop = InventoryItem::create([
            'school_id' => $this->school->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Asset Laptop',
            'item_type' => 'fixed_asset',
            'unit_of_measure' => 'pieces',
            'reorder_level' => 0,
            'current_quantity' => 0,
            'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $laptop;

        $order = $this->seedOrder([['item_id' => $laptop->id, 'qty' => 3, 'cost' => 800]], 'sent');

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = GoodsReceivedItem::create([
            'goods_received_note_id' => $grn->id,
            'inventory_item_id' => $laptop->id,
            'quantity_accepted' => 3,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $assets = DB::table('fixed_assets')
            ->where('school_id', $this->school->id)
            ->where('inventory_item_id', $laptop->id)
            ->get();
        $this->created[] = (object) ['delete' => fn () => DB::table('fixed_assets')->where('inventory_item_id', $laptop->id)->delete()];

        $this->assertCount(3, $assets, 'Each accepted unit must become an asset record.');
        $this->assertSame('800.00', number_format((float) $assets->first()->purchase_cost, 2));
        $this->assertSame(3, (int) $order->refresh()->items()->first()->quantity_received);
        $this->assertSame('completed', $order->refresh()->status);
    }

    public function test_a_mixed_order_records_stock_and_assets_independently(): void
    {
        $category = InventoryCategory::create(['school_id' => $this->school->id, 'name' => 'Mixed '.uniqid()]);
        $this->created[] = $category;

        $consumable = InventoryItem::create([
            'school_id' => $this->school->id, 'category_id' => $category->id, 'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Toner', 'item_type' => 'consumable', 'unit_of_measure' => 'pieces',
            'reorder_level' => 1, 'current_quantity' => 0, 'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $consumable;

        $asset = InventoryItem::create([
            'school_id' => $this->school->id, 'category_id' => $category->id, 'sku' => 'SKU-'.uniqid(),
            'name' => 'Test Server', 'item_type' => 'fixed_asset', 'unit_of_measure' => 'pieces',
            'reorder_level' => 0, 'current_quantity' => 0, 'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $asset;

        // Ten ordered lines, as the user described, only some of which arrived.
        $order = $this->seedOrder([
            ['item_id' => $consumable->id, 'qty' => 5, 'cost' => 20],
            ['item_id' => $asset->id, 'qty' => 2, 'cost' => 500],
        ], 'sent');

        $this->created[] = (object) ['delete' => fn () => DB::table('fixed_assets')->where('inventory_item_id', $asset->id)->delete()];

        $rows = collect(GoodsReceivedNote::deliveryRows($order->load('items')))
            ->keyBy('inventory_item_id');

        // The user deletes the line that did not arrive and reduces another.
        $received = [
            ['inventory_item_id' => $consumable->id, 'quantity_accepted' => 5, 'quantity_rejected' => 0],
            ['inventory_item_id' => $asset->id, 'quantity_accepted' => 1, 'quantity_rejected' => 1],
        ];
        $this->assertSame(5, $rows[$consumable->id]['quantity_outstanding']);
        $this->assertSame(2, $rows[$asset->id]['quantity_outstanding']);

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        foreach ($received as $line) {
            $created = $grn->items()->create($line);
            $this->created[] = $created;
        }

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $this->assertSame(5, (int) $consumable->refresh()->current_quantity, 'Consumables go into stock.');
        $this->assertSame(
            1,
            DB::table('fixed_assets')->where('inventory_item_id', $asset->id)->count(),
            'Only the accepted asset unit is registered; the rejected one is not.'
        );
        // receiveGoods runs updateMovingAverageCost() for every accepted line
        // and additionally capitalises fixed assets, so an accepted asset is
        // recorded in both the stock ledger and the asset register.
        $this->assertSame(1, (int) $asset->refresh()->current_quantity);

        $order->refresh();
        $this->assertSame(5, (int) $order->items()->firstWhere('inventory_item_id', $consumable->id)->quantity_received);
        $this->assertSame(1, (int) $order->items()->firstWhere('inventory_item_id', $asset->id)->quantity_received);
        $this->assertSame('partially_received', $order->status, 'The order stays open until the rest arrives.');
    }

    public function test_editing_a_posted_note_does_not_double_count_stock(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 4, 'cost' => 100]], 'sent');

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = $grn->items()->create([
            'inventory_item_id' => $item->id,
            'quantity_accepted' => 4,
            'quantity_rejected' => 0,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);
        $this->assertSame(4, (int) $item->refresh()->current_quantity);

        $component = Livewire::test(EditGoodsReceivedNote::class, ['record' => $grn->getKey()])
            ->assertSuccessful()
            ->assertFormSet(function (array $state): array {
                $items = array_values($state['items']);
                $this->assertCount(1, $items);
                $this->assertSame(4, $items[0]['quantity_ordered']);
                $this->assertSame(4, $items[0]['quantity_accepted'], 'The posted quantity is shown as-is.');
                // Outstanding is measured with this note's own quantity backed
                // out, so it stays the ceiling this note may hold.
                $this->assertSame(0, $items[0]['quantity_received_before']);
                $this->assertSame(4, $items[0]['quantity_outstanding']);

                return [];
            });

        $component->call('save')->assertHasNoFormErrors();

        $this->assertSame(4, (int) $item->refresh()->current_quantity, 'Editing must not post stock a second time.');
        $this->assertSame(4, (int) $order->refresh()->items()->first()->quantity_received);
    }

    public function test_order_view_lists_every_ordered_line_with_its_quantities(): void
    {
        [, $laptops] = $this->seedCategoryAndItem();

        $category = InventoryCategory::create(['school_id' => $this->school->id, 'name' => 'View Cat '.uniqid()]);
        $this->created[] = $category;
        $monitors = InventoryItem::create([
            'school_id' => $this->school->id, 'category_id' => $category->id, 'sku' => 'SKU-'.uniqid(),
            'name' => 'View Test Monitor', 'item_type' => 'consumable', 'unit_of_measure' => 'pieces',
            'reorder_level' => 1, 'current_quantity' => 0, 'average_unit_cost' => 0.0000,
        ]);
        $this->created[] = $monitors;

        $order = $this->seedOrder([
            ['item_id' => $laptops->id, 'qty' => 5, 'cost' => 100],
            ['item_id' => $monitors->id, 'qty' => 2, 'cost' => 250],
        ], 'approved');

        $html = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString($laptops->name, $html, 'Every ordered item must be listed on the order.');
        $this->assertStringContainsString($monitors->name, $html);
        $this->assertStringContainsString('0/2', $html, 'Both lines are counted and neither is complete yet.');
        $this->assertStringContainsString('Ordered Items and Goods Received', $html);
        $this->assertStringContainsString('Unit Cost', $html);
        $this->assertStringContainsString('$500.00', $html, 'The order shows the value of each line like the requisition does.');
        $this->assertStringNotContainsString('no ordered items yet', $html);
    }

    public function test_receiving_comparison_is_the_single_source_for_the_order_screen_and_pdf(): void
    {
        [, $laptops] = $this->seedCategoryAndItem();

        $order = $this->seedOrder([
            ['item_id' => $laptops->id, 'qty' => 4, 'cost' => 125],
        ], 'approved');

        $rows = $order->receivingComparison();

        $this->assertCount(1, $rows);
        $this->assertSame($laptops->name, $rows[0]['item']);
        $this->assertSame(4, $rows[0]['ordered']);
        $this->assertSame(0, $rows[0]['received']);
        $this->assertSame(4, $rows[0]['outstanding']);
        $this->assertSame(125.0, $rows[0]['unit_cost']);
        $this->assertSame(500.0, $rows[0]['line_total']);
        $this->assertSame('pending', $rows[0]['state']);
        $this->assertSame(0, $rows[0]['grn_count']);

        $grn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $grn;

        $grnItem = $grn->items()->create([
            'inventory_item_id' => $laptops->id,
            'quantity_accepted' => 3,
            'quantity_rejected' => 1,
        ]);
        $this->created[] = $grnItem;

        app(ProcurementPipelineService::class)->receiveGoods($grn);

        $rows = $order->refresh()->receivingComparison();

        $this->assertSame(3, $rows[0]['received'], 'Received is summed from the notes.');
        $this->assertSame(1, $rows[0]['rejected']);
        $this->assertSame(1, $rows[0]['outstanding']);
        $this->assertSame(1, $rows[0]['grn_count']);
        $this->assertSame('partial', $rows[0]['state']);

        // A stale loaded relation must not be able to blank the comparison.
        $this->assertCount(1, $order->items()->get());
        $this->assertCount(1, $order->receivingComparison());
    }

    public function test_the_printed_comparison_pdf_renders_the_same_lines(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([
            ['item_id' => $item->id, 'qty' => 6, 'cost' => 20],
        ], 'approved');

        $response = Livewire::test(ViewPurchaseOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful()
            ->callAction('print');

        $response->assertFileDownloaded($order->order_number.'-GRN-Comparison.pdf');

        // The same builder feeds the PDF, so the ordered quantity is in there.
        $pdf = ViewPurchaseOrder::streamComparisonPdf($order);
        ob_start();
        $pdf->sendContent();
        $body = (string) ob_get_clean();

        $this->assertStringContainsString('%PDF', substr($body, 0, 5));
        $this->assertGreaterThan(1000, strlen($body), 'The comparison PDF rendered a real document.');
    }

    public function test_an_order_with_no_ordered_items_cannot_be_approved(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $account = $this->seedBankAccount(5000);
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 1, 'cost' => 500]], 'draft');

        // Reproduce the state found on the server: a header total with the
        // line rows gone, which used to move real money.
        $order->items()->delete();
        $order->forceFill(['total_amount' => 500])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no ordered items');

        try {
            app(ProcurementPipelineService::class)->approveOrder($order, $account->id);
        } finally {
            $this->assertNotSame('approved', $order->refresh()->status, 'The order must not be approved.');
            $this->assertSame(5000.0, (float) $account->refresh()->balance, 'No money may leave the bank.');
            $this->assertSame(0, Expense::where('reference_number', 'EXP-PO-'.$order->order_number)->count());
        }
    }

    public function test_approval_takes_the_money_from_the_lines_not_the_header_total(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $account = $this->seedBankAccount(5000);
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 4, 'cost' => 100]], 'draft');

        // A header that drifted from its lines must not move the wrong amount.
        $order->forceFill(['total_amount' => 999])->save();

        app(ProcurementPipelineService::class)->approveOrder($order, $account->id);

        $this->assertSame(400.00, (float) $order->refresh()->total_amount, 'The approved total is the sum of the lines.');
        $this->assertSame(4600.0, (float) $account->refresh()->balance, 'The deduction matches the lines.');
        $this->assertSame(400.00, (float) Expense::where('reference_number', 'EXP-PO-'.$order->order_number)->value('amount'));
    }

    public function test_a_purchase_order_form_cannot_be_saved_without_any_items(): void
    {
        $this->seedCategoryAndItem();

        $before = ProcurementOrder::withoutGlobalScopes()->count();

        Livewire::test(CreatePurchaseOrder::class)
            ->fillForm([
                'supplier_id' => $this->seedSupplier(),
                'order_date' => now()->toDateString(),
                'items' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['items']);

        $this->assertSame($before, ProcurementOrder::withoutGlobalScopes()->count(), 'An empty order must not be created.');
    }

    public function test_the_repair_command_restores_missing_lines_without_touching_matching_money(): void
    {
        [, $laptop] = $this->seedCategoryAndItem();
        $account = $this->seedBankAccount(5000);

        $request = ProcurementRequest::create([
            'school_id' => $this->school->id,
            'request_number' => 'PR-'.uniqid(),
            'requester_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $this->created[] = $request;
        $this->created[] = $request->items()->create([
            'item_name' => $laptop->name,
            'inventory_item_id' => $laptop->id,
            'quantity' => 1,
            'estimated_unit_cost' => 500,
            'is_fixed_asset' => false,
        ]);

        $order = $this->seedOrder([['item_id' => $laptop->id, 'qty' => 1, 'cost' => 500]], 'approved');
        $order->forceFill(['procurement_request_id' => $request->id])->save();

        // The exact server state: header total and money recorded, line rows gone.
        $order->items()->delete();
        $account->decrement('balance', 500);

        $expenseCategory = ExpenseCategory::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Procurement & Inventory'],
            ['description' => 'test fixture']
        );
        $expenseType = ExpenseType::firstOrCreate([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'name' => 'Inventory & Asset Procurement',
        ]);

        Expense::create([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'expense_type_id' => $expenseType->id,
            'expense_name' => 'Procurement (PO '.$order->order_number.')',
            'amount' => 500,
            'expense_date' => now()->toDateString(),
            'reference_number' => 'EXP-PO-'.$order->order_number,
            'status' => 'paid',
            'bank_account_id' => $account->id,
        ]);

        $this->assertSame(0, $order->items()->count(), 'The order starts with no lines.');

        // Dry run: reports, writes nothing.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
        ])->assertSuccessful();

        $this->assertSame(0, $order->items()->count(), 'A dry run must not write.');

        // Apply: the rebuilt lines match the recorded expense, so money stands.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(1, $order->items()->count());
        $line = $order->items()->first();
        $this->assertSame((int) $laptop->id, (int) $line->inventory_item_id);
        $this->assertSame(1, (int) $line->quantity_ordered);
        $this->assertSame(0, (int) $line->quantity_received);
        $this->assertSame(500.00, (float) $order->refresh()->total_amount);
        $this->assertSame(4500.0, (float) $account->refresh()->balance, 'Matching money is left alone as recorded.');
        $this->assertSame(1, Expense::where('reference_number', 'EXP-PO-'.$order->order_number)->count());

        // Idempotent: a second run must not add another line.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(1, $order->items()->count(), 'Running it twice must not duplicate the lines.');
    }

    public function test_the_repair_command_will_not_guess_an_unknown_item(): void
    {
        [, $laptop] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $laptop->id, 'qty' => 1, 'cost' => 500]], 'approved');
        $order->items()->delete();

        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--item' => 'A Product That Does Not Exist|1|500',
            '--apply' => true,
        ])->assertFailed();

        $this->assertSame(0, $order->items()->count(), 'Nothing may be written for an unresolvable item.');
    }

    public function test_the_repair_command_refuses_to_move_money_without_the_ledger_flag(): void
    {
        [, $laptop] = $this->seedCategoryAndItem();
        $account = $this->seedBankAccount(5000);
        $order = $this->seedOrder([['item_id' => $laptop->id, 'qty' => 1, 'cost' => 500]], 'approved');

        $order->items()->delete();
        $account->decrement('balance', 800);

        $expenseCategory = ExpenseCategory::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Procurement & Inventory'],
            ['description' => 'test fixture']
        );
        $expenseType = ExpenseType::firstOrCreate([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'name' => 'Inventory & Asset Procurement',
        ]);

        Expense::create([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'expense_type_id' => $expenseType->id,
            'expense_name' => 'Procurement (PO '.$order->order_number.')',
            'amount' => 800,
            'expense_date' => now()->toDateString(),
            'reference_number' => 'EXP-PO-'.$order->order_number,
            'status' => 'paid',
            'bank_account_id' => $account->id,
        ]);

        // No --reverse-ledger, so the mismatch is reported and nothing changes.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--item' => $laptop->name.'|1|500',
            '--apply' => true,
        ])->assertFailed();

        $this->assertSame(0, $order->items()->count(), 'The lines wait until the money question is settled.');
        $this->assertSame(4200.0, (float) $account->refresh()->balance, 'No money moves without the flag.');

        // With the flag, the expense is re-issued at the real value and the
        // over-deduction is put back.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--item' => $laptop->name.'|1|500',
            '--apply' => true,
            '--reverse-ledger' => true,
        ])->assertSuccessful();

        $this->assertSame(1, $order->items()->count());
        $this->assertSame(500.00, (float) $order->refresh()->total_amount);

        $expense = Expense::where('reference_number', 'EXP-PO-'.$order->order_number)->first();
        $this->assertNotNull($expense, 'The expense is re-issued rather than left stale.');
        $this->assertSame(500.00, (float) $expense->amount);
        $this->assertSame(4500.0, (float) $account->refresh()->balance, 'The 300 over-deduction is returned.');
    }

    public function test_the_repair_command_only_removes_empty_received_note_headers(): void
    {
        [, $laptop] = $this->seedCategoryAndItem();
        $order = $this->seedOrder([['item_id' => $laptop->id, 'qty' => 1, 'cost' => 500]], 'approved');
        $order->items()->delete();

        // A leftover GRN header with no item rows, exactly like the server one.
        $emptyGrn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $emptyGrn;

        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--item' => $laptop->name.'|1|500',
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(1, $order->items()->count(), 'The line is restored.');
        $this->assertNull(GoodsReceivedNote::find($emptyGrn->id), 'The empty received-note header is removed.');

        // A GRN that actually posted goods must still block the repair.
        [, $monitor] = $this->seedCategoryAndItem();
        $order2 = $this->seedOrder([['item_id' => $monitor->id, 'qty' => 2, 'cost' => 300]], 'approved');
        $order2->items()->delete();

        $postedGrn = GoodsReceivedNote::create([
            'school_id' => $this->school->id,
            'procurement_order_id' => $order2->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => $this->user->id,
        ]);
        $this->created[] = $postedGrn;
        $this->created[] = $postedGrn->items()->create([
            'inventory_item_id' => $monitor->id,
            'quantity_accepted' => 2,
            'quantity_rejected' => 0,
        ]);

        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order2->order_number,
            '--item' => $monitor->name.'|2|300',
            '--apply' => true,
        ])->assertFailed();

        $this->assertSame(0, $order2->items()->count(), 'A posted note blocks the repair.');
        $this->assertNotNull(GoodsReceivedNote::find($postedGrn->id), 'A posted note is never deleted.');
    }

    public function test_the_repair_command_can_delete_the_order_and_put_the_money_back(): void
    {
        [, $item] = $this->seedCategoryAndItem();
        $account = $this->seedBankAccount(5000);
        $order = $this->seedOrder([['item_id' => $item->id, 'qty' => 1, 'cost' => 500]], 'approved');
        $order->items()->delete();

        $expenseCategory = ExpenseCategory::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Procurement & Inventory'],
            ['description' => 'test fixture']
        );
        $expenseType = ExpenseType::firstOrCreate([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'name' => 'Inventory & Asset Procurement',
        ]);

        $account->decrement('balance', 500);
        $expense = Expense::create([
            'school_id' => $this->school->id,
            'expense_category_id' => $expenseCategory->id,
            'expense_type_id' => $expenseType->id,
            'expense_name' => 'Procurement (PO '.$order->order_number.')',
            'amount' => 500,
            'expense_date' => now()->toDateString(),
            'reference_number' => 'EXP-PO-'.$order->order_number,
            'status' => 'paid',
            'bank_account_id' => $account->id,
        ]);

        $this->assertSame(0, $order->items()->count());

        // Dry run deletes nothing.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--delete-order' => true,
        ])->assertSuccessful();

        $this->assertNotNull(ProcurementOrder::find($order->id), 'A dry run must not delete.');
        $this->assertNotNull(Expense::find($expense->id));

        // Apply deletes the order and puts the $500 back.
        $this->artisan('schoolcore:repair-purchase-order-lines', [
            'school' => $this->school->id,
            '--order' => $order->order_number,
            '--delete-order' => true,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertNull(ProcurementOrder::find($order->id), 'The order is gone.');
        $this->assertNull(Expense::find($expense->id), 'The expense is gone.');
        $this->assertSame(5000.0, (float) $account->refresh()->balance, 'The bank balance is back to before the order.');
    }
}
