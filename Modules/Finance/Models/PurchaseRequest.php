<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use BelongsToTenant;

    protected $table = 'purchase_requests';

    protected $fillable = [
        'school_id',
        'supplier_id',
        'title',
        'description',
        'estimated_amount',
        'status', // pending, approved, rejected, ordered, paid
        'requested_by_id',
        'approved_by_id',
        'approval_notes',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
