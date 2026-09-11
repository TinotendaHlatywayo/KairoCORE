<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'academic_year_id', 'name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($term) {
            if (! $term->school_id) {
                $term->school_id = current_tenant()?->id
                    ?? auth()->user()?->school_id
                    ?? $term->academicYear?->school_id;
            }
        });

        static::saving(function ($term) {
            if (! $term->school_id) {
                $term->school_id = current_tenant()?->id
                    ?? auth()->user()?->school_id
                    ?? $term->academicYear?->school_id;
            }

            if ($term->is_active && $term->school_id && $term->academic_year_id) {
                static::where('school_id', $term->school_id)
                    ->where('academic_year_id', $term->academic_year_id)
                    ->where('id', '!=', $term->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
