<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementOrderItem;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Services\ProcurementPipelineService;

/**
 * One-off repair for purchase orders that carry a header total but no line rows.
 *
 * Approving such an order used to move real money (an expense plus a bank
 * balance deduction) while nothing was ever ordered, because the approval
 * service trusted the stored total and never checked that lines existed. The
 * lines have since been rebuilt on the affected orders, or the ledger movement
 * reversed, by hand.
 *
 * This command reconstructs the missing lines from the requisition the order was
 * raised from, so the recorded expense and bank deduction can be checked
 * against the real value of the order. It refuses to guess: if it cannot
 * resolve a line unambiguously it stops and reports instead of writing.
 *
 * It is a dry run unless --apply is passed, and it is idempotent: an order that
 * already has lines is left untouched.
 */
class RepairPurchaseOrderLines extends Command
{
    protected $signature = 'schoolcore:repair-purchase-order-lines
        {school : School ID that owns the order}
        {--order= : Order number, e.g. PO-2026-00001}
        {--item= : "Name|quantity|unit_cost" when the order has no requisition to rebuild from; repeat for several lines}
        {--delete-order : Reverse the approved payment and delete the order instead of rebuilding its lines}
        {--apply : Actually write. Without this the command only reports what it would do.}
        {--reverse-ledger : When the rebuilt total differs from the recorded expense, delete the expense and put the difference back on the bank account}';

    protected $description = 'Rebuild the ordered-item lines of a purchase order that has a header total but no lines, or delete the order and reverse its payment with --delete-order. Dry run unless --apply.';

    public function handle(): int
    {
        $schoolId = (int) $this->argument('school');
        $orderNumber = $this->option('order');

        if (! $orderNumber) {
            $this->components->error('Pass the order number, e.g. --order=PO-2026-00001.');

            return self::FAILURE;
        }

        $order = ProcurementOrder::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('order_number', $orderNumber)
            ->orderBy('id')
            ->get();

        if ($order->isEmpty()) {
            $this->components->error("No order {$orderNumber} for school {$schoolId}.");

            return self::FAILURE;
        }

        if ($order->count() > 1) {
            $this->components->error("School {$schoolId} has {$order->count()} orders numbered {$orderNumber}. Narrow this down before repairing anything.");

            return self::FAILURE;
        }

        /** @var ProcurementOrder $order */
        $order = $order->first();

        $existingLines = ProcurementOrderItem::where('procurement_order_id', $order->id)->count();

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>School</>', (string) $schoolId);
        $this->components->twoColumnDetail('<fg=gray>Order</>', $order->order_number.' (id '.$order->id.')');
        $this->components->twoColumnDetail('<fg=gray>Status</>', (string) $order->status);
        $this->components->twoColumnDetail('<fg=gray>Header total</>', '$'.number_format((float) $order->total_amount, 2));
        $this->components->twoColumnDetail('<fg=gray>Line rows</>', (string) $existingLines);
        $this->newLine();

        $grns = GoodsReceivedNote::withoutGlobalScopes()
            ->withCount('items')
            ->where('procurement_order_id', $order->id)
            ->get();

        $postedGrns = $grns->filter(fn (GoodsReceivedNote $grn): bool => $grn->items_count > 0);

        if ($postedGrns->isNotEmpty()) {
            $this->components->error('Goods have already been received against this order ('.implode(', ', $postedGrns->pluck('grn_number')->all()).'). Neither rebuilding nor deleting is safe while those notes exist. Repair this one by hand.');

            return self::FAILURE;
        }

        if ($this->option('delete-order')) {
            return $this->deleteOrder($order, $grns, $existingLines);
        }

        if ($existingLines > 0) {
            $this->components->info('This order already has item lines, so there is nothing to repair. Nothing was changed.');

            return self::SUCCESS;
        }

        // A received-note header with no item rows carries no stock or asset
        // movement; it is a leftover of a save that never posted anything and
        // only stands in the way here, so it will be removed with the repair.
        $emptyGrnHeaders = $grns->count();

        if ($emptyGrnHeaders > 0) {
            $this->components->warn("Found {$grns->count()} empty received-note header(s) on this order (no item rows, so nothing was posted). They will be removed with --apply.");
        }

        $lines = $this->resolveLines($order, $schoolId);

        if ($lines === null) {
            return self::FAILURE;
        }

        if ($lines === []) {
            $this->components->error('No lines could be resolved, so nothing was written.');

            return self::FAILURE;
        }

        $newTotal = round(array_sum(array_map(
            fn (array $line): float => $line['quantity_ordered'] * $line['unit_cost'],
            $lines
        )), 2);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Lines to restore</>', (string) count($lines));
        $this->table(
            ['#', 'Inventory item', 'Qty', 'Unit cost', 'Line total'],
            array_map(fn (array $line, int $i): array => [
                $i + 1,
                $line['name'],
                $line['quantity_ordered'],
                '$'.number_format($line['unit_cost'], 2),
                '$'.number_format($line['quantity_ordered'] * $line['unit_cost'], 2),
            ], $lines, array_keys($lines))
        );
        $this->components->twoColumnDetail('<fg=gray>Rebuilt total</>', '$'.number_format($newTotal, 2));
        $this->components->twoColumnDetail('<fg=gray>Header total</>', '$'.number_format((float) $order->total_amount, 2));
        $this->newLine();

        $expense = Expense::where('school_id', $schoolId)
            ->where('reference_number', 'EXP-PO-'.$order->order_number)
            ->orderBy('id')
            ->first();

        $ledgerDelta = $this->reportLedger($order, $expense, $newTotal);

        if (! $this->option('apply')) {
            $this->newLine();
            $this->components->info('Dry run. Re-run with --apply to make these changes.');

            return self::SUCCESS;
        }

        if (! $this->apply($order, $lines, $newTotal, $expense, $ledgerDelta, $grns)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Work out what the item lines should be, or null when it cannot be done
     * without guessing.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function resolveLines(ProcurementOrder $order, int $schoolId): ?array
    {
        $request = $order->procurement_request_id
            ? ProcurementRequest::withoutGlobalScopes()->with('items')->find($order->procurement_request_id)
            : null;

        if ($request && $request->items->isNotEmpty()) {
            $this->components->info('Rebuilding from requisition '.$request->request_number.'.');

            return $this->linesFromRequisition($request, $schoolId);
        }

        $manual = $this->option('item');

        if (! $manual) {
            $this->components->error('This order is not linked to a requisition, so the lines cannot be inferred. Pass them explicitly, e.g. --item="Laptop|1|500".');

            return null;
        }

        $lines = [];

        foreach ((array) $manual as $spec) {
            $parts = explode('|', (string) $spec);

            if (count($parts) !== 3) {
                $this->components->error("Could not read --item={$spec}. Use Name|quantity|unit_cost.");

                return null;
            }

            [$name, $quantity, $unitCost] = $parts;

            if ((int) $quantity < 1) {
                $this->components->error("Quantity for {$name} must be at least 1.");

                return null;
            }

            $item = $this->findInventoryItem($schoolId, $name);

            if ($item === null) {
                return null;
            }

            $lines[] = [
                'inventory_item_id' => (int) $item->id,
                'name' => $item->name,
                'quantity_ordered' => (int) $quantity,
                'unit_cost' => (float) $unitCost,
                'is_fixed_asset' => (bool) $item->is_fixed_asset,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function linesFromRequisition(ProcurementRequest $request, int $schoolId): ?array
    {
        $lines = [];

        foreach ($request->items as $item) {
            $inventoryItemId = $item->inventory_item_id;

            if (! $inventoryItemId) {
                // The requisition item was never linked to the catalog; the PO
                // generation path used to link it. Resolve it by name here.
                $matched = $this->findInventoryItem($schoolId, (string) $item->item_name);

                if ($matched === null) {
                    return null;
                }

                $inventoryItemId = $matched->id;
            }

            $lines[] = [
                'inventory_item_id' => (int) $inventoryItemId,
                'name' => $item->item_name,
                'quantity_ordered' => (int) $item->quantity,
                'unit_cost' => (float) $item->estimated_unit_cost,
                'is_fixed_asset' => (bool) $item->is_fixed_asset,
            ];
        }

        return $lines;
    }

    /**
     * Resolve a catalog item by name, refusing to guess when the name is
     * missing or matches more than one item.
     */
    private function findInventoryItem(int $schoolId, string $name): ?InventoryItem
    {
        $matches = InventoryItem::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->orderBy('id')
            ->get();

        if ($matches->isEmpty()) {
            $this->components->error("No catalog item named {$name} in this school, so the line cannot be attached to anything.");

            return null;
        }

        if ($matches->count() > 1) {
            $this->components->error("Catalog item {$name} is ambiguous in this school (ids: {$matches->pluck('id')->implode(', ')}). Resolve the duplicates first.");

            return null;
        }

        return $matches->first();
    }

    /**
     * Describe the ledger movement that was recorded when the order was
     * approved, and how far it is from the rebuilt total.
     */
    private function reportLedger(ProcurementOrder $order, ?Expense $expense, float $newTotal): float
    {
        if (! $expense) {
            $this->newLine();
            $this->components->warn('No expense recorded for this order, so no ledger movement needs correcting.');

            return 0.0;
        }

        $recorded = round((float) $expense->amount, 2);
        $difference = round($newTotal - $recorded, 2);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Recorded expense</>', '$'.number_format($recorded, 2).' (id '.$expense->id.')');
        $this->components->twoColumnDetail('<fg=gray>Rebuilt total</>', '$'.number_format($newTotal, 2));

        if (abs($difference) < 0.005) {
            $this->components->info('The recorded expense matches the rebuilt lines, so the money is correct and only the missing lines need restoring.');

            return 0.0;
        }

        $this->components->warn('The recorded expense does not match the rebuilt lines by $'.number_format(abs($difference), 2).'.');

        if (! $this->option('reverse-ledger')) {
            $this->components->warn('Add --reverse-ledger to delete the expense and put the difference back on bank account id '.(int) $expense->bank_account_id.'. Nothing will be moved without it.');

            return $difference;
        }

        return $difference;
    }

    /**
     * Remove the order and undo the payment approval recorded for it, returning
     * the bank account to what it was before the procurement.
     *
     * @param  Collection<int, GoodsReceivedNote>  $grns
     */
    private function deleteOrder(ProcurementOrder $order, $grns, int $existingLines): int
    {
        if (! $this->option('apply')) {
            $this->components->info('This would delete the order and reverse its payment. Re-run with --apply to do it.');

            return self::SUCCESS;
        }

        if ((float) $order->refunded_amount > 0) {
            $this->components->error('This order has a recorded refund of $'.number_format((float) $order->refunded_amount, 2).'. Reversing the payment on top of that needs care, so this is left for a manual review.');

            return self::FAILURE;
        }

        $expense = Expense::where('school_id', $order->school_id)
            ->where('reference_number', 'EXP-PO-'.$order->order_number)
            ->orderBy('id')
            ->first();

        $reversed = $expense
            ? round((float) $expense->amount, 2)
            : 0.0;

        DB::transaction(function () use ($order, $grns, $expense, $reversed, $existingLines): void {
            if ($expense) {
                if ($expense->bank_account_id) {
                    $account = SchoolBankAccount::find($expense->bank_account_id);

                    if ($account) {
                        $account->increment('balance', $reversed);
                    }
                }

                $expense->delete();
            }

            if ($grns->isNotEmpty()) {
                GoodsReceivedNote::withoutGlobalScopes()
                    ->whereIn('id', $grns->pluck('id')->all())
                    ->delete();
            }

            if ($existingLines > 0) {
                ProcurementOrderItem::where('procurement_order_id', $order->id)->delete();
            }

            $order->delete();
        });

        $this->newLine();
        $this->components->info('Deleted '.$order->order_number.' and put $'.number_format($reversed, 2).' back on the bank account, so the finances are back to before this order.');

        return self::SUCCESS;
    }

    /**
     * Write the rebuilt lines, fix the header total, and reverse the ledger
     * movement when asked and when it no longer matches.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  Collection<int, GoodsReceivedNote>  $grns
     */
    private function apply(ProcurementOrder $order, array $lines, float $newTotal, ?Expense $expense, float $ledgerDelta, $grns): bool
    {
        $reverse = $expense && abs($ledgerDelta) >= 0.005;

        if ($reverse && ! $this->option('reverse-ledger')) {
            $this->components->error('The recorded expense and bank deduction do not match the rebuilt lines, so nothing was written. Re-run with --reverse-ledger, or settle the ledger by hand first.');

            return false;
        }

        $reversing = $reverse && $this->option('reverse-ledger');

        DB::transaction(function () use ($order, $lines, $newTotal, $expense, $reversing, $grns): void {
            foreach ($lines as $line) {
                ProcurementOrderItem::create([
                    'procurement_order_id' => $order->id,
                    'inventory_item_id' => $line['inventory_item_id'],
                    'quantity_ordered' => $line['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'is_fixed_asset' => $line['is_fixed_asset'],
                ]);
            }

            // Leftover received-note headers that never held a single item row
            // are noise, not receiving history, so they go with the repair.
            if ($grns->isNotEmpty()) {
                GoodsReceivedNote::withoutGlobalScopes()
                    ->whereIn('id', $grns->pluck('id')->all())
                    ->delete();
            }

            $order->forceFill([
                'total_amount' => $newTotal,
                // A zero total cannot be approved, so send it back for editing.
                'status' => $newTotal > 0 ? $order->status : 'draft',
            ])->save();

            if ($reversing) {
                $recorded = round((float) $expense->amount, 2);

                $expense->delete();

                if ($expense->bank_account_id) {
                    $account = SchoolBankAccount::find($expense->bank_account_id);

                    if ($account) {
                        // The order was only ever deducted, never credited, so
                        // the full recorded amount goes back.
                        $account->increment('balance', $recorded);
                    }
                }

                if ($newTotal > 0) {
                    Expense::create([
                        'school_id' => $order->school_id,
                        'expense_category_id' => $expense->expense_category_id,
                        'expense_type_id' => $expense->expense_type_id,
                        // No supplier_id: the order's supplier is an inventory
                        // supplier, which is not the suppliers table this
                        // column points at. Approval does not set it either.
                        'expense_name' => 'Procurement (PO '.$order->order_number.')',
                        'amount' => $newTotal,
                        'expense_date' => $order->order_date?->toDateString() ?? now()->toDateString(),
                        'reference_number' => 'EXP-PO-'.$order->order_number,
                        'notes' => 'Re-issued from repaired purchase order lines.',
                        'status' => 'paid',
                        'bank_account_id' => $expense->bank_account_id,
                    ]);

                    if ($expense->bank_account_id) {
                        $account = SchoolBankAccount::find($expense->bank_account_id);

                        if ($account) {
                            $account->decrement('balance', $newTotal);
                        }
                    }
                }
            }

            // Recompute from the freshly written rows so the header can never
            // disagree with the lines it now owns.
            app(ProcurementPipelineService::class)->recomputeOrderTotal($order->refresh());
        });

        $this->newLine();
        $this->components->info('Restored '.count($lines).' line(s) on '.$order->order_number.'. Total is now $'.number_format((float) $order->refresh()->total_amount, 2).'.');

        if ($grns->isNotEmpty()) {
            $this->components->info('Removed '.count($grns).' empty received-note header(s) that never posted any goods.');
        }

        if ($reversing) {
            $this->components->info('Reversed the old expense and moved the bank balance to match.');
        }

        return true;
    }
}
