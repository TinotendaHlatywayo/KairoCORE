<?php

namespace Modules\Academics\Services;

use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\GradingScale;

/**
 * Resolves the grading scale a school configured under
 * "Grading & Marks Management → Grading Scales" and maps percentage scores to
 * the scale's symbols.
 *
 * Everywhere a grade is displayed (report card subject grades, the grading key
 * footnote, performance analytics) must go through this resolver so the school's
 * own scale is honoured instead of a hard-coded A..U table.
 */
class GradingScaleResolver
{
    /**
     * Memoised bands per school id (a school's scale is stable within a
     * request), so per-student lookups never re-query the database.
     *
     * @var array<int|string, list<array{symbol: string, min: float, max: float, remark: string}>>
     */
    private static array $bandsCache = [];

    /**
     * Default bands used only when the school has not configured a scale.
     *
     * @var list<array{symbol: string, min: float, max: float, remark: string}>
     */
    public const DEFAULT_BANDS = [
        ['symbol' => 'A', 'min' => 80, 'max' => 100, 'remark' => 'Distinction'],
        ['symbol' => 'B', 'min' => 70, 'max' => 79, 'remark' => 'Merit'],
        ['symbol' => 'C', 'min' => 60, 'max' => 69, 'remark' => 'Credit'],
        ['symbol' => 'D', 'min' => 50, 'max' => 59, 'remark' => 'Pass'],
        ['symbol' => 'E', 'min' => 40, 'max' => 49, 'remark' => 'Marginal Pass'],
        ['symbol' => 'U', 'min' => 0, 'max' => 39, 'remark' => 'Ungraded'],
    ];

    /**
     * The school's grading scale (bands already loaded). When several scales
     * exist the first one is used, matching the convention used elsewhere in
     * the codebase.
     */
    public static function forSchool(?int $schoolId): ?GradingScale
    {
        if (! $schoolId) {
            return null;
        }

        return GradingScale::withoutGlobalScopes()
            ->with('points')
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->first();
    }

    /**
     * Drop the memoised bands (mainly useful for tests).
     */
    public static function flushCache(): void
    {
        self::$bandsCache = [];
    }

    /**
     * Bands for a school (configured scale, or the fallback when none exists).
     *
     * @return list<array{symbol: string, min: float, max: float, remark: string}>
     */
    public static function bands(?int $schoolId): array
    {
        $cacheKey = $schoolId ?? 0;
        if (array_key_exists($cacheKey, self::$bandsCache)) {
            return self::$bandsCache[$cacheKey];
        }

        $scale = self::forSchool($schoolId);

        if ($scale && $scale->points->isNotEmpty()) {
            $bands = $scale->points
                ->sortBy(fn (GradingPoint $point) => (float) $point->min_score)
                ->values()
                ->map(fn (GradingPoint $point) => [
                    'symbol' => $point->symbol ?: '-',
                    'min' => (float) $point->min_score,
                    'max' => (float) $point->max_score,
                    'remark' => $point->remark ?? '',
                ])
                ->all();
        } else {
            $bands = self::DEFAULT_BANDS;
        }

        // Always return bands ordered ascending by score so callers can rely on
        // a stable worst→best ordering regardless of how the scale was stored.
        usort($bands, fn (array $a, array $b): float => $a['min'] <=> $b['min']);

        return self::$bandsCache[$cacheKey] = $bands;
    }

    /**
     * The band a percentage score maps to.
     *
     * @return array{symbol: string, min: float, max: float, remark: string}
     */
    public static function rating(float|int|null $percentage, ?int $schoolId): ?array
    {
        if ($percentage === null || $percentage === '') {
            return null;
        }

        $percentage = (float) $percentage;

        foreach (self::bands($schoolId) as $band) {
            if ($percentage >= $band['min'] && $percentage <= $band['max']) {
                return $band;
            }
        }

        // Out-of-range scores (gaps between configured bands) map to the band
        // whose boundary sits closest to the score.
        $closest = null;
        $closestDistance = PHP_FLOAT_MAX;
        foreach (self::bands($schoolId) as $band) {
            $distance = min(abs($percentage - $band['min']), abs($percentage - $band['max']));
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $band;
            }
        }

        return $closest;
    }

    /**
     * A "symbol => min-max" map for printing the grading key (footer).
     *
     * @return array<string, string>
     */
    public static function key(?int $schoolId): array
    {
        $key = [];
        // Display best→worst, like a printed academic key.
        foreach (array_reverse(self::bands($schoolId)) as $band) {
            $key[$band['symbol']] = self::formatRange($band['min'], $band['max']);
        }

        return $key;
    }

    /**
     * A Filament badge tone derived from the symbol's position in the scale
     * (higher band = greener). Falls back to gray for unknown symbols.
     */
    public static function tone(?int $schoolId, string $symbol): string
    {
        $symbols = array_column(self::bands($schoolId), 'symbol');
        $index = array_search($symbol, $symbols, true);

        if ($index === false) {
            return 'gray';
        }

        // Bands are ordered ascending by score, so a higher index is a better
        // grade: position measured from the top maps to green..red.
        $length = max(count($symbols), 1);
        $position = ($length - 1 - $index) / max($length - 1, 1);

        if ($position < 0.25) {
            return 'success';
        }

        if ($position < 0.5) {
            return 'info';
        }

        if ($position < 0.8) {
            return 'warning';
        }

        return 'danger';
    }

    public static function formatRange(float $min, float $max): string
    {
        $min = round($min);
        $max = round($max);

        if ($min === $max) {
            return (string) $min;
        }

        return (string) $min.'-'.$max;
    }
}