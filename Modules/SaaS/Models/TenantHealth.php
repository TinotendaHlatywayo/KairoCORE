<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;

class TenantHealth extends Model
{
    protected $table = 'tenant_healths';

    protected $fillable = [
        'school_id',
        'uptime_percentage',
        'response_time_ms',
        'storage_used_bytes',
        'active_users_count',
        'health_logs',
    ];

    protected $casts = [
        'health_logs' => 'array',
        'uptime_percentage' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
