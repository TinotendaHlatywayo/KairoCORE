<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'procurement_order_id',
        'grn_number',
        'received_date',
        'received_by_id',
        'delivery_challan_number',
        'supplier_invoice_number',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    /**
     * GRN numbers are issued by the system, never typed by the user.
     */
    protected static function booted(): void
    {
        static::creating(function (self $grn): void {
            if (blank($grn->grn_number)) {
                $grn->grn_number = self::nextNumber((int) $grn->school_id);
            }
        });
    }

    /**
     * Next sequential GRN number for a school, e.g. GRN-2026-00007.
     *
     * Sequential rather than random so the numbers are readable, and the
     * existence check keeps it safe if numbers have been deleted or a
     * previous note was created concurrently.
     */
    public static function nextNumber(int $schoolId): string
    {
        $prefix = 'GRN-'.now()->year.'-';

        $highest = static::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('grn_number', 'like', $prefix.'%')
            ->pluck('grn_number')
            ->map(fn ($number): int => (int) substr((string) $number, strlen($prefix)))
            ->filter(fn ($number): bool => $number > 0)
            ->max() ?? 0;

        do {
            $number = $prefix.str_pad((string) ++$highest, 5, '0', STR_PAD_LEFT);
        } while (static::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('grn_number', $number)
            ->exists());

        return $number;
    }

    /**
     * Build the ordered-vs-received comparison rows for a purchase order.
     *
     * Every line on the order is returned so the user can compare the whole
     * order and delete or adjust the lines that did not arrive. When editing
     * an existing note, the quantities that note already recorded are backed
     * out first, so the outstanding figure is what could still be received.
     *
     * @return array<int, array<string, int>>
     */
    public static function deliveryRows(ProcurementOrder $order, ?self $grn = null): array
    {
        $alreadyOnThisNote = [];

        if ($grn) {
            foreach ($grn->items as $item) {
                $alreadyOnThisNote[$item->inventory_item_id] = (int) $item->quantity_accepted;
            }
        }

        return $order->items
            ->map(function (ProcurementOrderItem $line) use ($alreadyOnThisNote): array {
                $ordered = (int) $line->quantity_ordered;
                $onThisNote = $alreadyOnThisNote[$line->inventory_item_id] ?? 0;
                $receivedBefore = max(0, (int) $line->quantity_received - $onThisNote);
                $outstanding = max(0, $ordered - $receivedBefore);

                return [
                    'inventory_item_id' => (int) $line->inventory_item_id,
                    'quantity_ordered' => $ordered,
                    'quantity_received_before' => $receivedBefore,
                    'quantity_outstanding' => $outstanding,
                    // A new note defaults to receiving everything outstanding.
                    'quantity_accepted' => $onThisNote > 0 ? $onThisNote : $outstanding,
                    'quantity_rejected' => 0,
                ];
            })
            ->values()
            ->all();
    }

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function procurementOrder(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrder::class, 'procurement_order_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceivedItem::class, 'goods_received_note_id');
    }
}
