<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'email', 'phone', 'relationship'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_guardian');
    }
}
