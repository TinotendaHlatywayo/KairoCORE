<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($academicYear) {
            if ($academicYear->is_active) {
                // 1. Automatically turn off active status for all other years of this school
                static::where('school_id', $academicYear->school_id)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }

            // 2. Clear the active year cache for this school
            Cache::forget("school_{$academicYear->school_id}_active_year");
        });
    }

    public function terms()
    {
        return $this->hasMany(Term::class);
    }
}
