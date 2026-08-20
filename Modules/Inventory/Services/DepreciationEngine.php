<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Models\DepreciationSchedule;
use Modules\Inventory\Models\FixedAsset;

class DepreciationEngine
{
    /**
     * Compute potential depreciation schedule without writing to the database.
     * Useful for UI/preview simulations before locking/posting.
     */
    public function calculateSchedules(FixedAsset $asset): array
    {
        $cost = (float) $asset->purchase_cost;
        $salvage = (float) $asset->salvage_value;
        $life = (int) $asset->useful_life_years;
        $method = $asset->depreciation_method;

        if ($life <= 0) {
            throw new InvalidArgumentException('Useful life must be a positive integer greater than zero.');
        }

        if ($salvage > $cost) {
            throw new InvalidArgumentException("Salvage value cannot exceed the asset's initial purchase cost.");
        }

        $schedules = [];
        $bookValue = $cost;
        $depreciableAmount = $cost - $salvage;

        if ($method === 'straight_line') {
            $annualDepreciation = round($depreciableAmount / $life, 2);
            for ($year = 1; $year <= $life; $year++) {
                $startValue = $bookValue;
                // Avoid over-depreciating in the final year due to floating-point rounding
                if ($year === $life) {
                    $endValue = $salvage;
                } else {
                    $endValue = max($salvage, round($bookValue - $annualDepreciation, 2));
                }
                $actualDepr = round($startValue - $endValue, 2);

                $schedules[] = [
                    'fiscal_year' => now()->year + ($year - 1),
                    'depreciation_amount' => $actualDepr,
                    'book_value_start' => $startValue,
                    'book_value_end' => $endValue,
                ];
                $bookValue = $endValue;
            }
        } elseif ($method === 'double_declining') {
            $rate = 2 / $life;
            for ($year = 1; $year <= $life; $year++) {
                $startValue = $bookValue;

                if ($year === $life) {
                    $actualDepr = max(0.00, round($startValue - $salvage, 2));
                    $endValue = $salvage;
                } else {
                    $potentialDepr = round($startValue * $rate, 2);
                    $endValue = max($salvage, round($startValue - $potentialDepr, 2));
                    $actualDepr = round($startValue - $endValue, 2);
                }

                $schedules[] = [
                    'fiscal_year' => now()->year + ($year - 1),
                    'depreciation_amount' => $actualDepr,
                    'book_value_start' => $startValue,
                    'book_value_end' => $endValue,
                ];
                $bookValue = $endValue;
            }
        }

        return $schedules;
    }

    /**
     * Compute, write, and post the depreciation schedules to the database.
     */
    public function postScheduleToLedger(FixedAsset $asset): void
    {
        DB::transaction(function () use ($asset) {
            $schedules = $this->calculateSchedules($asset);

            foreach ($schedules as $sched) {
                DepreciationSchedule::updateOrCreate(
                    [
                        'school_id' => $asset->school_id,
                        'fixed_asset_id' => $asset->id,
                        'fiscal_year' => $sched['fiscal_year'],
                    ],
                    [
                        'depreciation_amount' => $sched['depreciation_amount'],
                        'book_value_start' => $sched['book_value_start'],
                        'book_value_end' => $sched['book_value_end'],
                        'is_posted' => true,
                    ]
                );
            }

            // Reconcile current asset value matching the current fiscal year
            $currentYearSched = collect($schedules)->firstWhere('fiscal_year', now()->year);
            if ($currentYearSched) {
                $asset->update(['current_value' => $currentYearSched['book_value_end']]);
            }
        });
    }
}
