<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FeeWaiver extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'type', 'value'];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
