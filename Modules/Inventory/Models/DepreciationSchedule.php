<?php

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationSchedule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'fixed_asset_id',
        'fiscal_year',
        'depreciation_amount',
        'book_value_start',
        'book_value_end',
        'is_posted',
    ];

    protected $casts = [
        'depreciation_amount' => 'decimal:2',
        'book_value_start' => 'decimal:2',
        'book_value_end' => 'decimal:2',
        'is_posted' => 'boolean',
        'fiscal_year' => 'integer',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
