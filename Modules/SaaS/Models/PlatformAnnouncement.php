<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAnnouncement extends Model
{
    protected $table = 'platform_announcements';

    protected $fillable = [
        'title',
        'content',
        'type',
        'target_plans',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'target_plans' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
