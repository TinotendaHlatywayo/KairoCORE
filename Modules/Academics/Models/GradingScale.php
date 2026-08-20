<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    use BelongsToTenant;

    protected $table = 'grading_scales';

    protected $fillable = [
        'school_id',
        'name',
    ];

    /**
     * Relationship: A grading scale contains multiple grade points/ranges.
     */
    public function points(): HasMany
    {
        return $this->hasMany(GradingPoint::class, 'grading_scale_id');
    }
}
