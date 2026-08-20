<?php

namespace Modules\Reports\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    use BelongsToTenant;

    protected $table = 'generated_reports';

    protected $fillable = [
        'school_id',
        'enterprise_report_template_id',
        'name',
        'format',
        'file_path',
        'status',
        'error_message',
        'generated_by_id',
        'record_count',
        'execution_ms',
        'data_checksum',
        'data_validated',
        'validated_at',
        'summary',
        'filters_used',
        'is_downloaded',
    ];

    protected $casts = [
        'summary' => 'array',
        'filters_used' => 'array',
        'is_downloaded' => 'boolean',
        'record_count' => 'integer',
        'execution_ms' => 'integer',
        'data_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EnterpriseReportTemplate::class, 'enterprise_report_template_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }
}
