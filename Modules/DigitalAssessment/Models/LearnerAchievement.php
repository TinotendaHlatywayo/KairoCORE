<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerAchievement extends Model
{
    protected $table = 'learner_achievements';

    protected $fillable = [
        'school_id',
        'student_id',
        'gamification_achievement_id',
        'earned_at',
        'notified',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'notified' => 'boolean',
    ];

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(GamificationAchievement::class, 'gamification_achievement_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }
}
