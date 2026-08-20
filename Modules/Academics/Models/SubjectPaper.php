<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SubjectPaper extends Model
{
    use BelongsToTenant;

    protected $table = 'subject_papers';

    protected $fillable = ['school_id', 'subject_id', 'name'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
