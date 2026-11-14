<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Server-side builders for the Tinway dashboard charts. Each method returns a
 * self-contained SVG string (plus data-tip attributes consumed by the shared
 * dashboard tooltip script) so every chart is rendered from live data on the
 * server and survives Livewire morphing without a re-run of client code.
 *
 * The math mirrors the original tinway-dashboard.html prototype (bar heights,
 * donut arc offsets, smooth-curve area charts and sparklines) so the visuals
 * stay identical to the static design.
 */
class TinwayChartSvg
{
    /**
     * Horizontal bar chart of students per grade, drawn on the dark hero card.
     *
     * @param array<int, array{label: string, value: float|int}> $grades
     */
    public static function gradeBars(array $grades, string $unit): string
    {
        if ($grades === []) {
            return '';
        }

        $max = (float) max(array_column($grades, 'value'));

        if ($max <= 0) {
            $max = 1.0;
        }

        $w = 46;
        $count = count($grades);
        $svg = '<svg class="gb" viewBox="0 0 '.$count * $w.' 150" width="100%" role="img">';

        foreach (array_values($grades) as $i => $grade) {
            $value = (float) $grade['value'];
            $hh = round($value / $max * 100, 2);
            $x = $i * $w + $w / 2;
            $svg .= '<g tabindex="0" data-tip="'.e($grade['label'].' · '.self::num($value).' '.$unit).'">'
                .'<rect x="'.($i * $w + 7).'" y="'.(120 - $hh).'" width="'.($w - 14).'" height="'.$hh.'" rx="8" fill="#fff" opacity="'.round(0.4 + 0.6 * $value / $max, 3).'" style="animation-delay:'.($i * 60).'ms"/>'
                .'<text x="'.$x.'" y="'.(114 - $hh).'" text-anchor="middle">'.self::num($value).'</text>'
                .'<text x="'.$x.'" y="141" text-anchor="middle">'.e(Str::limit($grade['label'], 10, '')).'</text>'
                .'</g>';
        }

        return $svg.'</svg>';
    }

    /**
     * Attendance donut. Segments are drawn clockwise starting at 12 o'clock.
     *
     * @param array<int, array{value: float|int, color: string, label: string}> $segments
     */
    public static function donut(array $segments, string $ariaLabel): string
    {
        $total = array_sum(array_map(static fn ($s) => (float) $s['value'], $segments));

        $circumference = 2 * M_PI * 48;
        $offset = 0.0;
        $circles = '';

        if ($total <= 0) {
            $circles = '<circle r="48" cx="68" cy="68" fill="none" stroke="var(--line)" stroke-width="14"/>';
        } else {
            foreach ($segments as $segment) {
                $value = (float) $segment['value'];
                if ($value <= 0) {
                    continue;
                }

                $length = $value / $total * $circumference - 4;
                $circles .= '<circle r="48" cx="68" cy="68" fill="none" stroke="'.$segment['color'].'" stroke-width="14" class="sg" tabindex="0" data-tip="'.e($segment['label'].': '.self::num($value)).'" stroke-linecap="round" stroke-dasharray="'.round($length, 2).' '.round($circumference - $length, 2).'" stroke-dashoffset="'.round(-$offset, 2).'" transform="rotate(-90 68 68)"/>';
                $offset += $value / $total * $circumference;
            }
        }

        $percent = $total > 0 ? (int) round($segments[0]['value'] / $total * 100) : 0;

        return '<svg viewBox="0 0 136 136" role="img" aria-label="'.e($ariaLabel).'">'.$circles
            .'<text class="mid" x="68" y="75" text-anchor="middle">'.$percent.'%</text></svg>';
    }

    /**
     * Smooth-curve area chart used for the fees timeline. The gradient id must
     * be unique per SVG on the page, hence the suffix.
     *
     * @param  array<int, array{label: string, value: float|int, tip?: string, last?: string}>  $points
     */
    public static function areaChart(array $points, string $suffix, string $ariaLabel = ''): string
    {
        if (count($points) < 2) {
            return '';
        }

        $W = 640;
        $H = 160;
        $values = array_map(static fn ($p) => (float) $p['value'], $points);
        $max = max($values) * 1.15;

        if ($max <= 0) {
            $max = 1.0;
        }

        $step = ($W - 30) / (count($points) - 1);
        $coords = [];

        foreach ($points as $i => $point) {
            $coords[] = [15 + $i * $step, $H - 24 - ((float) $point['value'] / $max) * ($H - 46)];
        }

        $d = 'M'.$coords[0][0].' '.round($coords[0][1], 2);

        for ($i = 1; $i < count($coords); $i++) {
            $a = $coords[$i - 1];
            $b = $coords[$i];
            $cx = ($a[0] + $b[0]) / 2;
            $d .= 'C'.$cx.' '.round($a[1], 2).' '.$cx.' '.round($b[1], 2).' '.round($b[0], 2).' '.round($b[1], 2);
        }

        [$lastX, $lastY] = $coords[count($coords) - 1];
        $firstX = $coords[0][0];
        $gradientId = 'twfg-'.$suffix;

        $svg = '<svg viewBox="0 0 '.$W.' '.$H.'" width="100%" role="img"'.($ariaLabel !== '' ? ' aria-label="'.e($ariaLabel).'"' : '').'>'
            .'<defs><linearGradient id="'.$gradientId.'" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="0" stop-color="#5b57f0" stop-opacity=".28"/>'
            .'<stop offset="1" stop-color="#5b57f0" stop-opacity="0"/>'
            .'</linearGradient></defs>'
            .'<path d="'.$d.'L'.round($lastX, 2).' '.($H - 24).'L'.$firstX.' '.($H - 24).'Z" class="ar" fill="url(#'.$gradientId.')"/>'
            .'<path class="ln" pathLength="1" d="'.$d.'" fill="none" stroke="#5b57f0" stroke-width="3" stroke-linecap="round"/>';

        foreach ($coords as $i => [$x, $y]) {
            $isLast = $i === count($coords) - 1;
            $tip = $points[$i]['tip'] ?? null;
            $svg .= '<circle cx="'.round($x, 2).'" cy="'.round($y, 2).'" r="'.($isLast ? 6 : 3.5).'" fill="'.($isLast ? '#5b57f0' : 'var(--card)').'" stroke="#5b57f0" stroke-width="2"'.($tip !== null ? ' tabindex="0" data-tip="'.e($tip).'"' : '').'/>'
                .'<text x="'.round($x, 2).'" y="'.($H - 5).'" text-anchor="middle">'.e($points[$i]['label']).'</text>';
        }

        if (isset($points[count($points) - 1]['last'])) {
            $svg .= '<text x="'.(round($lastX, 2) - 12).'" y="'.(round($lastY, 2) - 6).'" text-anchor="end" class="lastval">'.e($points[count($points) - 1]['last']).'</text>';
        }

        return $svg.'</svg>';
    }

    /**
     * Mini sparkline with a soft area fill below the line.
     *
     * @param array<int, float|int> $values
     */
    public static function sparkline(array $values): string
    {
        $count = count($values);

        if ($count < 2) {
            return '';
        }

        $w = 120;
        $h = 30;
        $min = (float) min($values);
        $max = (float) max($values);
        $range = ($max - $min) ?: 1.0;

        $d = '';

        foreach ($values as $i => $value) {
            $x = round($i * $w / ($count - 1), 1);
            $y = round($h - 3 - ((float) $value - $min) / $range * ($h - 6), 1);
            $d .= ($i === 0 ? 'M' : 'L').$x.' '.$y;
        }

        return '<svg class="spk" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none" aria-hidden="true">'
            .'<path d="'.$d.'L'.$w.' '.$h.'L0 '.$h.'Z" fill="var(--ac)" opacity=".1"/>'
            .'<path d="'.$d.'" fill="none" stroke="var(--ac)" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>';
    }

    protected static function num(float $value): string
    {
        return number_format($value, $value == floor($value) ? 0 : 1);
    }
}
