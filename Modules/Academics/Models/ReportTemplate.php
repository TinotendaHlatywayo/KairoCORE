<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'report_templates';

    protected $fillable = [
        'school_id',
        'name',
        'design_theme',
        'target_level',
        'scope_type', // level, course, section
        'course_id',
        'section_id',
        'is_active',
        'layout_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'layout_config' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saved(function (ReportTemplate $template) {
            if ($template->is_active) {
                $query = static::query()
                    ->where('id', '!=', $template->id);

                if ($template->school_id) {
                    $query->where('school_id', $template->school_id);
                }

                $query->update(['is_active' => false]);
            }
        });
    }

    public static array $themes = [
        'classic_line' => '1. Classic Academic (Solid Header Dividers)',
        'modern_grid' => '2. Modern Grid (Rounded panels & High Contrast)',
        'elegant_editorial' => '3. Elegant Editorial (Georgia Serif Accents)',
        'minimal_compact' => '4. Minimalist Compact (No Colour — Black & White)',
        'royal_crest' => '5. Royal Crest (Luxury Navy & Gold margins)',
    ];

    public static array $brackets = [
        'ecd' => 'ECD Only (Early Childhood)',
        'primary' => 'Primary School (Grades 1 to 7)',
        'lower_secondary' => 'Lower Secondary (O-Level / CBC)',
        'upper_secondary' => 'Upper Secondary (A-Level Multi-Paper)',
        'all' => 'Universal (All Brackets)',
    ];

    public static array $scopes = [
        'level' => 'Apply to Educational Level Bracket (e.g. Primary)',
        'course' => 'Apply to Specific Grade / Form Level (e.g. Form 1)',
        'section' => 'Apply to Specific Class Stream (e.g. Form 1 Arts)',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
