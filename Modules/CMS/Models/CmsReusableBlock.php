<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CmsReusableBlock extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'category',
        'description',
        'thumbnail',
        'content',
        'block_types',
        'is_global',
        'usage_count',
    ];

    protected $casts = [
        'content' => 'array',
        'block_types' => 'array',
        'is_global' => 'boolean',
    ];
}
