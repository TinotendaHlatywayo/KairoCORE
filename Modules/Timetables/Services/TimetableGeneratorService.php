<?php

namespace Modules\Timetables\Services;

use Carbon\Carbon;
use Modules\Timetables\Models\TimeSlot;

class TimetableGeneratorService
{
    public function generate(array $params, ?int $templateId = null): void
    {
        $schoolId = app('current_tenant')->id;

        $currentTime = Carbon::parse($params['start_time']);
        $endTimeOfLessons = Carbon::parse($params['end_time_of_lessons']);
        $periodLength = (int) $params['period_length'];

        $hasFixedBreak = (bool) ($params['has_fixed_break'] ?? false);
        $fixedBreakTime = ! empty($params['fixed_break_time']) ? Carbon::parse($params['fixed_break_time']) : null;
        $breakDuration = (int) ($params['break_duration'] ?? 15);

        $hasFixedLunch = (bool) ($params['has_fixed_lunch'] ?? false);
        $fixedLunchTime = ! empty($params['fixed_lunch_time']) ? Carbon::parse($params['fixed_lunch_time']) : null;
        $lunchDuration = (int) ($params['lunch_duration'] ?? 45);

        $breakAfterPeriod = (int) ($params['break_after_period'] ?? 3);
        $lunchAfterPeriod = (int) ($params['lunch_after_period'] ?? 5);

        $periodCount = 1;

        while ($currentTime->lt($endTimeOfLessons)) {
            $nextTime = $currentTime->copy()->addMinutes($periodLength);

            // 1. Intercept Closing Time Overrun Gaps
            if ($nextTime->gt($endTimeOfLessons)) {
                TimeSlot::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'template_id' => $templateId,
                        'name' => 'Free / Buffer Slot',
                    ],
                    [
                        'start_time' => $currentTime->format('H:i:s'),
                        'end_time' => $endTimeOfLessons->format('H:i:s'),
                        'is_break' => true,
                        'color' => '#f8fafc',
                    ]
                );
                break;
            }

            // 2. Fixed Tea Break Placement
            if ($hasFixedBreak && $fixedBreakTime) {
                if ($currentTime->lt($fixedBreakTime) && $nextTime->gt($fixedBreakTime)) {
                    TimeSlot::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'template_id' => $templateId,
                            'name' => 'Free Slot (Pre-Break)',
                        ],
                        [
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $fixedBreakTime->format('H:i:s'),
                            'is_break' => true,
                            'color' => '#f8fafc',
                        ]
                    );
                    $currentTime = $fixedBreakTime->copy();

                    continue;
                }

                if ($currentTime->eq($fixedBreakTime)) {
                    $breakEnd = $currentTime->copy()->addMinutes($breakDuration);
                    TimeSlot::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'template_id' => $templateId,
                            'name' => 'Tea Break',
                        ],
                        [
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $breakEnd->format('H:i:s'),
                            'is_break' => true,
                            'color' => '#fef3c7',
                        ]
                    );
                    $currentTime = $breakEnd->copy();

                    continue;
                }
            }

            // 3. Fixed Lunch Break Placement
            if ($hasFixedLunch && $fixedLunchTime) {
                if ($currentTime->lt($fixedLunchTime) && $nextTime->gt($fixedLunchTime)) {
                    TimeSlot::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'template_id' => $templateId,
                            'name' => 'Free Slot (Pre-Lunch)',
                        ],
                        [
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $fixedLunchTime->format('H:i:s'),
                            'is_break' => true,
                            'color' => '#f8fafc',
                        ]
                    );
                    $currentTime = $fixedLunchTime->copy();

                    continue;
                }

                if ($currentTime->eq($fixedLunchTime)) {
                    $lunchEnd = $currentTime->copy()->addMinutes($lunchDuration);
                    TimeSlot::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'template_id' => $templateId,
                            'name' => 'Lunch Break',
                        ],
                        [
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $lunchEnd->format('H:i:s'),
                            'is_break' => true,
                            'color' => '#fee2e2',
                        ]
                    );
                    $currentTime = $lunchEnd->copy();

                    continue;
                }
            }

            // 4. Flexible Period-Count Tea Break
            if (! $hasFixedBreak && $periodCount === ($breakAfterPeriod + 1)) {
                $breakEnd = $currentTime->copy()->addMinutes($breakDuration);
                TimeSlot::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'template_id' => $templateId,
                        'name' => 'Tea Break',
                    ],
                    [
                        'start_time' => $currentTime->format('H:i:s'),
                        'end_time' => $breakEnd->format('H:i:s'),
                        'is_break' => true,
                        'color' => '#fef3c7',
                    ]
                );
                $currentTime = $breakEnd->copy();
                $breakAfterPeriod = -1;

                continue;
            }

            // 5. Flexible Period-Count Lunch Break
            if (! $hasFixedLunch && $periodCount === ($lunchAfterPeriod + 1)) {
                $lunchEnd = $currentTime->copy()->addMinutes($lunchDuration);
                TimeSlot::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'template_id' => $templateId,
                        'name' => 'Lunch Break',
                    ],
                    [
                        'start_time' => $currentTime->format('H:i:s'),
                        'end_time' => $lunchEnd->format('H:i:s'),
                        'is_break' => true,
                        'color' => '#fee2e2',
                    ]
                );
                $currentTime = $lunchEnd->copy();
                $lunchAfterPeriod = -1;

                continue;
            }

            // 6. Create Standard Period
            TimeSlot::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'template_id' => $templateId,
                    'name' => 'Period '.$periodCount,
                ],
                [
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $nextTime->format('H:i:s'),
                    'is_break' => false,
                    'color' => '#ffffff',
                ]
            );

            $periodCount++;
            $currentTime = $nextTime->copy();
        }
    }
}
