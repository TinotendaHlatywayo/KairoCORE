<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Cross-tenant message between the platform (super admin) and a school tenant.
 *
 * - sender_type 'platform'  => sent by a super admin (school_id stays null)
 * - sender_type 'school'    => sent by a permitted school user (school_id = sender)
 * - recipient_type 'school' => delivered to one or many schools (tracked in recipients)
 * - recipient_type 'platform' => delivered to the super admin inbox
 */
class PlatformMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'platform_messages';

    protected $fillable = [
        'uuid',
        'thread_id',
        'sender_type',
        'sender_user_id',
        'school_id',
        'recipient_type',
        'recipient_scope',
        'target_meta',
        'subject',
        'body',
        'priority',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'target_meta' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PlatformMessage $message) {
            $message->uuid = (string) Str::uuid();
            if (empty($message->thread_id)) {
                $message->thread_id = (string) Str::uuid();
            }
        });
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id')->withoutGlobalScopes();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id')->withoutGlobalScopes();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PlatformMessageRecipient::class, 'message_id');
    }

    /**
     * All messages in the same conversation thread, oldest first.
     *
     * MUST bypass the tenant global scope: platform-sent messages carry
     * school_id = NULL, so the scope would hide the platform's side of the
     * conversation from tenants (they could never read replies). This is
     * safe — threads are only reachable via inbox records already scoped to
     * the viewer's own conversations by the resource queries.
     */
    public function threadMessages(): HasMany
    {
        return $this->hasMany(PlatformMessage::class, 'thread_id', 'thread_id')
            ->withoutGlobalScopes()
            ->orderBy('created_at');
    }

    public function isFromPlatform(): bool
    {
        return $this->sender_type === 'platform';
    }

    public function getSenderLabelAttribute(): string
    {
        return $this->isFromPlatform() ? platform_name() : ($this->school?->name ?? 'Tenant');
    }

    public function getTargetLabelAttribute(): string
    {
        if ($this->isToPlatform()) {
            return 'Super Admin';
        }
        if ($this->recipient_scope === 'all') {
            return 'All tenants';
        }
        if ($this->recipient_scope === 'selected') {
            return $this->recipients()->count().' tenant(s)';
        }
        $first = $this->recipients()->first();

        return $first?->school?->name ?? 'Single tenant';
    }

    public function isToPlatform(): bool
    {
        return $this->recipient_type === 'platform';
    }

    public function isBroadcast(): bool
    {
        return $this->isFromPlatform() && $this->recipient_scope === 'all';
    }
}
