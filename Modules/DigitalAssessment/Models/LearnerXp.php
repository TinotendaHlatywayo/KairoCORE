<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class LearnerXp extends Model
{
    use BelongsToTenant;

    protected $table = 'learner_xp';

    protected $fillable = [
        'school_id',
        'student_id',
        'total_xp',
        'current_level',
        'current_level_name',
    ];

    protected $casts = [
        'total_xp' => 'integer',
        'current_level' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class, 'learner_xp_id');
    }

    public function addXp(int $amount, string $type, ?string $description = null, ?Model $reference = null): void
    {
        $this->increment('total_xp', $amount);
        $this->recalculateLevel();

        XpTransaction::create([
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'learner_xp_id' => $this->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function recalculateLevel(): void
    {
        $settings = GamificationSettings::forSchool($this->school_id);
        $levels = $settings->level_config ?? GamificationSettings::defaultLevels();

        $currentLevel = 1;
        $currentName = 'Explorer';

        foreach ($levels as $levelData) {
            if ($this->total_xp >= $levelData['xp_threshold']) {
                $currentLevel = $levelData['level'];
                $currentName = $levelData['name'];
            }
        }

        $this->update([
            'current_level' => $currentLevel,
            'current_level_name' => $currentName,
        ]);
    }

    public static function forStudent(int $schoolId, int $studentId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId, 'student_id' => $studentId],
            [
                'total_xp' => 0,
                'current_level' => 1,
                'current_level_name' => 'Explorer',
            ]
        );
    }
}
