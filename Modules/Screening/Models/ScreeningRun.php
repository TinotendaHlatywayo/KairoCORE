<?php

namespace Modules\Screening\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academics\Models\AcademicYear;
use Modules\Promotion\Models\PromotionRun;

class ScreeningRun extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'source_academic_year_id',
        'target_academic_year_id',
        'promotion_run_id',
        'status',
        'score_basis',
        'academic_year_mode',
        'subject_ids',
        'term_ids',
        'academic_year_ids',
        'created_by_id',
        'committed_at',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'term_ids' => 'array',
        'academic_year_ids' => 'array',
        'committed_at' => 'datetime',
    ];

    public function sourceAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'source_academic_year_id');
    }

    public function targetAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    public function promotionRun(): BelongsTo
    {
        return $this->belongsTo(PromotionRun::class, 'promotion_run_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScreeningItem::class);
    }

    public function previewSummary(): array
    {
        $counts = $this->items()
            ->selectRaw('decision, count(*) as cnt')
            ->groupBy('decision')
            ->pluck('cnt', 'decision')
            ->toArray();

        return [
            'placed' => $counts['placed'] ?? 0,
            'unplaced' => $counts['unplaced'] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    public function criteriaSummary(): string
    {
        $basis = ($this->score_basis ?? 'overall') === 'subjects'
            ? __('Selected subject(s)')
            : __('Overall average (all subjects)');

        $period = $this->academic_year_mode === 'selected'
            ? $this->criteriaNameList('academic_year_ids', \Modules\Academics\Models\AcademicYear::class, 'name')
            : __('Current academic year');

        $terms = $this->criteriaNameList('term_ids', \Modules\Academics\Models\Term::class, 'name');

        return $basis.' · '.$period.($terms === null ? '' : ' · '.$terms);
    }

    protected function criteriaNameList(string $column, string $modelClass, string $attr): ?string
    {
        $ids = $this->{$column} ?? null;

        if (empty($ids)) {
            return null;
        }

        $names = $modelClass::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->pluck($attr)
            ->implode(', ');

        return $names === '' ? null : $names;
    }
}