<?php

namespace Modules\Academics\Services;

use App\Models\School;

class ZimsecGradingTemplates
{
    /**
     * Pre-filled ZIMSEC grading templates, keyed by import option.
     *
     * Bands are expressed as raw percentage intervals (min_score..max_score)
     * and represent common school-set equivalents. Schools are expected to
     * review and adjust the ranges to match their own marking policy.
     */
    public const TEMPLATES = [
        'zimsec_grade7' => [
            'name' => 'ZIMSEC Grade 7 (Primary)',
            'points' => [
                ['symbol' => 'A', 'min_score' => 75, 'max_score' => 100, 'remark' => 'Excellent'],
                ['symbol' => 'B', 'min_score' => 65, 'max_score' => 74, 'remark' => 'Very Good'],
                ['symbol' => 'C', 'min_score' => 55, 'max_score' => 64, 'remark' => 'Good'],
                ['symbol' => 'D', 'min_score' => 45, 'max_score' => 54, 'remark' => 'Satisfactory'],
                ['symbol' => 'E', 'min_score' => 30, 'max_score' => 44, 'remark' => 'Borderline'],
                ['symbol' => 'U', 'min_score' => 0, 'max_score' => 29, 'remark' => 'Ungraded'],
            ],
        ],
        'zimsec_olevel' => [
            'name' => 'ZIMSEC O\'Level',
            'points' => [
                ['symbol' => 'A', 'min_score' => 75, 'max_score' => 100, 'remark' => 'Excellent'],
                ['symbol' => 'B', 'min_score' => 65, 'max_score' => 74, 'remark' => 'Very Good'],
                ['symbol' => 'C', 'min_score' => 55, 'max_score' => 64, 'remark' => 'Good'],
                ['symbol' => 'D', 'min_score' => 45, 'max_score' => 54, 'remark' => 'Satisfactory'],
                ['symbol' => 'E', 'min_score' => 35, 'max_score' => 44, 'remark' => 'Borderline'],
                ['symbol' => 'F', 'min_score' => 25, 'max_score' => 34, 'remark' => 'Weak'],
                ['symbol' => 'G', 'min_score' => 15, 'max_score' => 24, 'remark' => 'Very Weak'],
                ['symbol' => 'U', 'min_score' => 0, 'max_score' => 14, 'remark' => 'Ungraded'],
            ],
        ],
        'zimsec_alevel' => [
            'name' => 'ZIMSEC A\'Level',
            'points' => [
                ['symbol' => 'A', 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent'],
                ['symbol' => 'B', 'min_score' => 70, 'max_score' => 79, 'remark' => 'Very Good'],
                ['symbol' => 'C', 'min_score' => 60, 'max_score' => 69, 'remark' => 'Good'],
                ['symbol' => 'D', 'min_score' => 50, 'max_score' => 59, 'remark' => 'Satisfactory'],
                ['symbol' => 'E', 'min_score' => 40, 'max_score' => 49, 'remark' => 'Borderline'],
                ['symbol' => 'U', 'min_score' => 0, 'max_score' => 39, 'remark' => 'Ungraded'],
            ],
        ],
        'zimsec_both' => [
            'name' => 'ZIMSEC O\'Level & A\'Level',
            'points' => [
                ['symbol' => 'A', 'min_score' => 75, 'max_score' => 100, 'remark' => 'Excellent'],
                ['symbol' => 'B', 'min_score' => 65, 'max_score' => 74, 'remark' => 'Very Good'],
                ['symbol' => 'C', 'min_score' => 55, 'max_score' => 64, 'remark' => 'Good'],
                ['symbol' => 'D', 'min_score' => 45, 'max_score' => 54, 'remark' => 'Satisfactory'],
                ['symbol' => 'E', 'min_score' => 35, 'max_score' => 44, 'remark' => 'Borderline'],
                ['symbol' => 'F', 'min_score' => 25, 'max_score' => 34, 'remark' => 'Weak'],
                ['symbol' => 'U', 'min_score' => 0, 'max_score' => 24, 'remark' => 'Ungraded'],
            ],
        ],
    ];

    /**
     * Import options offered for a school, based on its registered type.
     *
     * Primary schools get the Grade 7 scale only; everything else
     * (secondary, high, combined, college...) can import O'Level, A'Level,
     * or one combined scale covering both.
     */
    public static function optionsFor(?School $school): array
    {
        $type = strtolower((string) ($school?->institution_type ?? 'secondary'));

        if ($type === 'primary') {
            return ['zimsec_grade7' => 'ZIMSEC Grade 7 (Primary)'];
        }

        return [
            'zimsec_olevel' => 'ZIMSEC O\'Level',
            'zimsec_alevel' => 'ZIMSEC A\'Level',
            'zimsec_both' => 'ZIMSEC O\'Level & A\'Level (Combined)',
        ];
    }

    public static function template(string $key): ?array
    {
        $key = strtolower(trim($key));

        if (! isset(self::TEMPLATES[$key])) {
            return null;
        }

        return self::TEMPLATES[$key];
    }
}
