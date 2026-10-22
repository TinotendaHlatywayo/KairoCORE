<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAnnouncement extends Model
{
    /**
     * Every tenant, every role.
     */
    public const AUDIENCE_ALL = 'all';

    /**
     * Only the system administrators of the target tenants.
     */
    public const AUDIENCE_ADMINS = 'admins';

    /**
     * Everyone belonging to the target tenants.
     */
    public const AUDIENCE_TENANTS = 'tenants';

    /**
     * A named list of individual accounts.
     */
    public const AUDIENCE_USERS = 'users';

    protected $table = 'platform_announcements';

    protected $fillable = [
        'title',
        'content',
        'type',
        'audience',
        'target_plans',
        'target_school_ids',
        'target_user_ids',
        'target_roles',
        'starts_at',
        'ends_at',
        'is_active',
        'show_in_banner',
    ];

    protected $casts = [
        'target_plans' => 'array',
        'target_school_ids' => 'array',
        'target_user_ids' => 'array',
        'target_roles' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'show_in_banner' => 'boolean',
    ];

    /**
     * Whether the announcement may be shown to anybody at all right now.
     */
    public function isWithinSchedule(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
