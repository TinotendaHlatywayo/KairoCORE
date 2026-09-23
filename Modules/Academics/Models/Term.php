<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Services\BalanceCarryForwardService;

class Term extends Model
{
    use BelongsToTenant;

    /**
     * Source term ids captured on activation, keyed by the model instance, so
     * the saved hook can carry balances forward after the write is committed.
     */
    protected static array $pendingCarryForwardSources = [];

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

            // Detect an activation switch (fresh record or is_active flipped on).
            // Capture which term(s) were active before this one so the finance
            // layer can carry their unpaid balances forward once the change is
            // committed.
            $activating = $term->is_active && ! (bool) $term->getOriginal('is_active');

            $sourceIds = [];
            if ($activating && $term->school_id) {
                $sourceIds = static::withoutTenantScope()
                    ->where('school_id', $term->school_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $term->id)
                    ->pluck('id')
                    ->all();
            }
            static::$pendingCarryForwardSources[spl_object_id($term)] = $sourceIds;

            if ($term->is_active && $term->school_id && $term->academic_year_id) {
                static::where('school_id', $term->school_id)
                    ->where('academic_year_id', $term->academic_year_id)
                    ->where('id', '!=', $term->id)
                    ->update(['is_active' => false]);
            }
        });

        static::saved(function ($term) {
            $key = spl_object_id($term);
            $sourceIds = static::$pendingCarryForwardSources[$key] ?? [];
            unset(static::$pendingCarryForwardSources[$key]);

            if (! $term->is_active || ! $term->school_id || empty($sourceIds)) {
                return;
            }

            BalanceCarryForwardService::carryForward($sourceIds, $term->id);
        });
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
