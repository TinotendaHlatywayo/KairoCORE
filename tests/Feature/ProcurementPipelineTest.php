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
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages\CreateGoodsReceivedNote;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages\ViewPurchaseOrder;
use Modules\Inventory\Models\GoodsReceivedItem;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementOrderItem;
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
}