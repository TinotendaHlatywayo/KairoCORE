<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $table = 'suppliers';

    protected $fillable = [
        'school_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_number',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
