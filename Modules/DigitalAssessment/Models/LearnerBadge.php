<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerBadge extends Model
{
    protected $table = 'learner_badges';

    protected $fillable = [
        'school_id',
        'student_id',
        'gamification_badge_id',
        'earned_at',
        'notified',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'notified' => 'boolean',
    ];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(GamificationBadge::class, 'gamification_badge_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }
}
