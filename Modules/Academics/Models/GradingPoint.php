<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class GradingPoint extends Model
{
    protected $fillable = [
        'grading_scale_id',
        'symbol',
        'min_score',
        'max_score',
        'remark',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function gradingScale()
    {
        return $this->belongsTo(GradingScale::class);
    }
}
