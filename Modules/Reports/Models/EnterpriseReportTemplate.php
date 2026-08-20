<?php

namespace Modules\Reports\Models;

use App\Models\School;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterpriseReportTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'enterprise_report_templates';

    protected $fillable = [
        'school_id',
        'name',
        'module',
        'report_type',
        'report_category',
        'sharing_scope',
        'department_id',
        'orientation',
        'selected_fields',
        'layout_settings',
        'datasets',
        'joins',
        'filters',
        'grouping',
        'calculations',
        'sorting',
        'visualizations',
        'is_pinned',
        'is_favorite',
        'is_system',
        'config_version',
        'version',
        'last_run_at',
        'last_edited_by_id',
        'created_by_id',
    ];

    protected $casts = [
        'selected_fields' => 'array',
        'layout_settings' => 'array',
        'datasets' => 'array',
        'joins' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'calculations' => 'array',
        'sorting' => 'array',
        'visualizations' => 'array',
        'is_pinned' => 'boolean',
        'is_favorite' => 'boolean',
        'is_system' => 'boolean',
        'config_version' => 'integer',
        'version' => 'integer',
        'last_run_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'enterprise_report_template_id');
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class, 'enterprise_report_template_id');
    }
}
