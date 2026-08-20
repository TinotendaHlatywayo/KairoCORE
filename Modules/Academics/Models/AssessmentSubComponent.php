<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSubComponent extends Model
{
    protected $table = 'assessment_sub_components';

    protected $fillable = [
        'assessment_id',
        'name',
        'max_mark',
        'weight_percentage',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
