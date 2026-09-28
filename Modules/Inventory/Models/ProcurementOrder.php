<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Models\SchoolBankAccount;

class ProcurementOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'procurement_request_id',
        'supplier_id',
        'order_number',
        'order_date',
        'expected_delivery_date',
        'status',
        'total_amount',
        'bank_account_id',
        'approved_by_id',
        'approved_at',
        'refunded_amount',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'refunded_amount' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementOrderItem::class, 'procurement_order_id');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class, 'procurement_order_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }

    /**
     * The ordered-versus-received comparison, one row per ordered line.
     *
     * The lines are read straight from the database instead of the loaded
     * `items`/`grns` relations so the purchase order view, the printed
     * comparison PDF and the goods received form can never disagree about what
     * was ordered.
     *
     * @return array<int, array<string, mixed>>
     */
    public function receivingComparison(): array
    {
        $totals = [];

        foreach ($this->grns()->with('items')->get() as $grn) {
            foreach ($grn->items as $grnItem) {
                $itemId = (int) $grnItem->inventory_item_id;

                $totals[$itemId]['received'] = ($totals[$itemId]['received'] ?? 0) + (int) $grnItem->quantity_accepted;
                $totals[$itemId]['rejected'] = ($totals[$itemId]['rejected'] ?? 0) + (int) $grnItem->quantity_rejected;
                $totals[$itemId]['grn_count'] = ($totals[$itemId]['grn_count'] ?? 0) + 1;
            }
        }

        return ProcurementOrderItem::query()
            ->where('procurement_order_id', $this->getKey())
            ->with('inventoryItem')
            ->orderBy('id')
            ->get()
            ->map(function (ProcurementOrderItem $line) use ($totals): array {
                $itemId = (int) $line->inventory_item_id;
                $ordered = (int) $line->quantity_ordered;
                $grnCount = (int) ($totals[$itemId]['grn_count'] ?? 0);

                // Notes keep their own accepted quantity, so use the sum of the
                // notes here. Fall back to the running counter on the line for
                // orders received before that counter was maintained.
                $received = $grnCount > 0
                    ? (int) ($totals[$itemId]['received'] ?? 0)
                    : (int) $line->quantity_received;

                $rejected = (int) ($totals[$itemId]['rejected'] ?? 0);
                $outstanding = max(0, $ordered - $received);
                $unitCost = (float) $line->unit_cost;

                if ($received >= $ordered) {
                    $state = $ordered === 0 ? 'pending' : 'complete';
                } elseif ($received > 0) {
                    $state = 'partial';
                } else {
                    $state = 'pending';
                }

                return [
                    'inventory_item_id' => $itemId,
                    'item' => $line->inventoryItem?->name ?? __('Unlinked item'),
                    'is_fixed_asset' => (bool) $line->is_fixed_asset,
                    'ordered' => $ordered,
                    'received' => $received,
                    'rejected' => $rejected,
                    'outstanding' => $outstanding,
                    'grn_count' => $grnCount,
                    'unit_cost' => $unitCost,
                    'line_total' => $unitCost * $ordered,
                    'state' => $state,
                    'state_label' => match ($state) {
                        'complete' => __('Complete'),
                        'partial' => __('Partial'),
                        default => __('Not Received'),
                    },
                    'state_color' => match ($state) {
                        'complete' => 'bg-emerald-100 text-emerald-700',
                        'partial' => 'bg-amber-100 text-amber-700',
                        default => 'bg-gray-100 text-gray-700',
                    },
                ];
            })
            ->all();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
