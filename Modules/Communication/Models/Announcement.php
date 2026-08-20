<?php

namespace Modules\Communication\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'priority',
        'display_style',
        'requires_acknowledgement',
    ];

    protected $casts = [
        'attachments' => 'array',
        'visibility' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'requires_acknowledgement' => 'boolean',
    ];

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
