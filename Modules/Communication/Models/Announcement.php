<?php

namespace Modules\Communication\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'communication_announcements';

    protected $fillable = [
        'school_id',
        'title',
        'content',
        'attachments',
        'published_at',
        'expires_at',
        'status',
        'visibility',
        'target_user_ids',
        'priority',
        'display_style',
        'requires_acknowledgement',
        'attachment_policy',
        'channel',
    ];

    protected $casts = [
        'attachments' => 'array',
        'visibility' => 'array',
        'target_user_ids' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'requires_acknowledgement' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id')->withoutGlobalScopes();
    }

    /**
     * Scope to retrieve currently valid published notices.
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
