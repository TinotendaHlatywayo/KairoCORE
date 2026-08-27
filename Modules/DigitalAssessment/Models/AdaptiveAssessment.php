<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class AdaptiveAssessment extends Model
{
    use BelongsToTenant;

    protected $table = 'adaptive_assessments';

    protected $fillable = [
        'school_id',
        'digital_assessment_id',
        'is_active',
        'base_difficulty',
        'min_difficulty',
        'max_difficulty',
        'window_size',
        'adjustment_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_difficulty' => 'integer',
        'min_difficulty' => 'integer',
        'max_difficulty' => 'integer',
        'window_size' => 'integer',
        'adjustment_rate' => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(DigitalAssessment::class, 'digital_assessment_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AdaptiveRule::class, 'adaptive_assessment_id');
    }
}
