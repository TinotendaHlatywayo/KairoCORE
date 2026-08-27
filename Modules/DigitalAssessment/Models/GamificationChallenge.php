<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DigitalAssessment\Enums\ChallengeStatus;
use Modules\DigitalAssessment\Enums\ChallengeType;
use App\Traits\BelongsToTenant;

class GamificationChallenge extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'gamification_challenges';

    protected $fillable = [
        'school_id',
        'created_by_id',
        'title',
        'description',
        'challenge_type',
        'target_subject_id',
        'target_topic',
        'target_count',
        'reward_xp',
        'reward_badge_id',
        'start_at',
        'end_at',
        'status',
        'settings',
    ];

    protected $casts = [
        'challenge_type' => ChallengeType::class,
        'status' => ChallengeStatus::class,
        'target_count' => 'integer',
        'reward_xp' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'settings' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function targetSubject(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Subject::class, 'target_subject_id');
    }

    public function rewardBadge(): BelongsTo
    {
        return $this->belongsTo(GamificationBadge::class, 'reward_badge_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChallengeParticipant::class, 'gamification_challenge_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ChallengeStatus::Active)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->status === ChallengeStatus::Active
            && $this->start_at->isPast()
            && $this->end_at->isFuture();
    }
}
