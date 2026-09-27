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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
