<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Term;

class FeeStructure extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'fee_category_id',
        'scope_type', // single, all, form_1_4, form_5_6, grade_1_7, ecd
        'course_id',
        'academic_year_id',
        'term_id',
        'currency',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static array $scopes = [
        'all' => 'Whole School (All Grades)',
        'form_1_4' => 'Form 1 to 4 (Junior Secondary)',
        'form_5_6' => 'Form 5 & 6 / Six (A-Level)',
        'grade_1_7' => 'Grade 1 to 7 (Primary)',
        'ecd' => 'ECD Only (Early Childhood)',
        'single' => 'Single Class Level Only',
    ];

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
