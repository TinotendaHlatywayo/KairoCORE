<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class LeaderboardSnapshot extends Model
{
    use BelongsToTenant;

    protected $table = 'leaderboard_snapshots';

    protected $fillable = [
        'school_id',
        'snapshot_date',
        'snapshot_type',
        'scope_type',
        'scope_id',
        'student_id',
        'score',
        'rank_position',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'score' => 'integer',
        'rank_position' => 'integer',
        'metadata' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('snapshot_date', $date);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    public function scopeForScope($query, string $scopeType, ?int $scopeId = null)
    {
        return $query->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId);
    }
}
