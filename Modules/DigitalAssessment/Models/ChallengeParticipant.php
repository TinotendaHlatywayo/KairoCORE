<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeParticipant extends Model
{
    protected $table = 'challenge_participants';

    protected $fillable = [
        'school_id',
        'gamification_challenge_id',
        'student_id',
        'progress',
        'completed',
        'completed_at',
        'xp_earned',
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'xp_earned' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(GamificationChallenge::class, 'gamification_challenge_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function incrementProgress(): void
    {
        $this->increment('progress');

        if ($this->progress >= $this->challenge->target_count && ! $this->completed) {
            $this->update([
                'completed' => true,
                'completed_at' => now(),
                'xp_earned' => $this->challenge->reward_xp,
            ]);
        }
    }
}
