<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use BelongsToTenant;

    protected $table = 'accounts';

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'type', // asset, liability, equity, revenue, expense
        'normal_balance', // debit, credit
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lineItems()
    {
        return $this->hasMany(JournalLineItem::class);
    }
}
