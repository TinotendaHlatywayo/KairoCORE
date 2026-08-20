<?php

namespace Modules\SaaS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformAuditLog extends Model
{
    protected $table = 'platform_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'details',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
