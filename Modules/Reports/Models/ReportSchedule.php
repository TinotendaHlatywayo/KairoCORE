<?php

namespace Modules\Reports\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use BelongsToTenant;

    protected $table = 'enterprise_report_schedules';

    protected $fillable = [
        'school_id',
        'enterprise_report_template_id',
        'name',
        'frequency',
        'distribution_method',
        'output_format',
        'generate_on_demand',
        'recipients',
        'filter_overrides',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'filter_overrides' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'is_active' => 'boolean',
        'generate_on_demand' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EnterpriseReportTemplate::class, 'enterprise_report_template_id');
    }
}
