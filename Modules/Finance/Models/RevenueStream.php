<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RevenueStream extends Model
{
    use BelongsToTenant;

    protected $table = 'revenue_streams';

    protected $fillable = [
        'school_id',
        'revenue_category_id',
        'name',
        'default_amount',
        'account_id',
        'is_active',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RevenueCategory::class, 'revenue_category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
