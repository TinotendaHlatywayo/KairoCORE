<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-school delivery/read tracking for platform->tenant messages.
 */
class PlatformMessageRecipient extends Model
{
    use BelongsToTenant;

    protected $table = 'platform_message_recipients';

    protected $fillable = [
        'message_id',
        'school_id',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(PlatformMessage::class, 'message_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id')->withoutGlobalScopes();
    }

    public function markRead(): void
    {
        if ($this->status !== 'read') {
            $this->update(['status' => 'read', 'read_at' => now()]);
        }
    }
}
