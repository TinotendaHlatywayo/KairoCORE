<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'capacity',
        'location',
        'description',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
        'capacity' => 'integer',
    ];
}
