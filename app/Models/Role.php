<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'guard_name',
    ];
}
