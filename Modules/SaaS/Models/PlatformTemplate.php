<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformTemplate extends Model
{
    protected $table = 'platform_templates';

    protected $fillable = [
        'name',
        'category',
        'preview_image',
        'configuration_blueprint',
        'is_active',
    ];

    protected $casts = [
        'configuration_blueprint' => 'array',
        'is_active' => 'boolean',
    ];
}
