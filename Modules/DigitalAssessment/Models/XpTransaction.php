<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DigitalAssessment\Enums\XpType;

class XpTransaction extends Model
{
    public $timestamps = false;

    protected $table = 'xp_transactions';

    protected $fillable = [
        'school_id',
        'student_id',
        'learner_xp_id',
        'amount',
        'type',
        'description',
        'reference_type',
        'reference_id',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'type' => XpType::class,
        'created_at' => 'datetime',
    ];

    public function learnerXp(): BelongsTo
    {
        return $this->belongsTo(LearnerXp::class, 'learner_xp_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }
}
