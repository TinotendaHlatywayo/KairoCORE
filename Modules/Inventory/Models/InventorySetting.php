<?php

namespace Modules\Inventory\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'active_modules',
        'valuation_method',
        'low_stock_notification_roles',
        'auto_bill_issued_uniforms',
    ];

    protected $casts = [
        'active_modules' => 'array',
        'low_stock_notification_roles' => 'array',
        'auto_bill_issued_uniforms' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
