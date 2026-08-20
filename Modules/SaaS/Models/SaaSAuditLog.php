<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaaSAuditLog extends Model
{
    protected $table = 'saas_audit_logs';

    protected $fillable = [
        'school_id', 'performed_by_id', 'event_type', 'ip_address',
        'user_agent', 'payload_before', 'payload_after',
    ];

    protected $casts = [
        'payload_before' => 'array',
        'payload_after' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
