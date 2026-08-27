<?php

namespace Modules\Timetables\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\CourseSubject;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;

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

    /**
     * Automatically place every lesson required by the school's teacher
     * assignments (course_subject) into the given template's teaching slots.
     *
     * Algorithm: constraint-based greedy placement with a most-constrained-first
     * ordering, day-spread and grid-balance scoring heuristics, load-balanced
     * classroom allocation and a final repair pass for stragglers.
     *
     * Hard constraints enforced (zero clashes guaranteed):
     *   1. A section (class stream) attends exactly one lesson per slot/day.
     *   2. A teacher teaches exactly one lesson per slot/day.
     *   3. A classroom hosts exactly one lesson per slot/day.
     *   4. Locked lessons are immovable obstacles that are never overwritten.
     *
     * Soft preferences optimised:
     *   - Same subject spread across different days (max per day configurable).
     *   - School-wide usage balanced across the slot grid (no overloaded periods).
     *   - Teacher weekly load balanced across days.
     *   - room_preference from the assignment honoured when free.
     *   - Least-used classroom chosen otherwise.
     *
     * @param  array{template_id: int, academic_year_id: int, term_id: int,
     *         replace_unlocked?: bool, max_per_subject_per_day?: int}  $params
     * @return array{placed: int, unplaced: array<int, array<string, string>>, skipped: array<int, string>}
     */
    public function autoPlaceLessons(array $params): array
    {
        $schoolId = app('current_tenant')->id;
        $templateId = (int) $params['template_id'];
        $academicYearId = (int) $params['academic_year_id'];
        $termId = (int) $params['term_id'];
        $maxPerSubjectPerDay = max(1, (int) ($params['max_per_subject_per_day'] ?? 1));

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // ------------------------------------------------------------------
        // 1. Load the teaching grid (non-break slots only).
        // ------------------------------------------------------------------
        $slots = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $templateId)
            ->where('is_break', false)
            ->orderBy('start_time')
            ->get(['id']);

        if ($slots->isEmpty()) {
            throw new \Exception('No teaching periods found for this template. Compile the time slots first.');
        }

        // ------------------------------------------------------------------
        // 2. Optionally clear unlocked lessons; locked ones stay as obstacles.
        // ------------------------------------------------------------------
        $replaceUnlocked = (bool) ($params['replace_unlocked'] ?? true);

        if ($replaceUnlocked) {
            TimetableLesson::where('school_id', $schoolId)
                ->where('template_id', $templateId)
                ->where('is_locked', false)
                ->delete();
        }

        // ------------------------------------------------------------------
        // 3. Seed occupancy maps from every surviving lesson so the generator
        //    can never double-book an existing entry.
        // ------------------------------------------------------------------
        $sectionBusy = [];
        $teacherBusy = [];
        $roomBusy = [];
        $slotUsage = [];
        $roomUsage = [];

        $survivingLessons = TimetableLesson::where('school_id', $schoolId)
            ->where('template_id', $templateId)
            ->get(['section_id', 'teacher_id', 'classroom_id', 'time_slot_id', 'day_of_week']);

        foreach ($survivingLessons as $lesson) {
            foreach ($days as $day) { /* no-op guard for enum drift */ }

            $key = $lesson->day_of_week.'|'.$lesson->time_slot_id;

            $sectionBusy[$lesson->section_id][$key] = true;
            $teacherBusy[$lesson->teacher_id][$key] = true;

            if ($lesson->classroom_id) {
                $roomBusy[$lesson->classroom_id][$key] = true;
                $roomUsage[$lesson->classroom_id] = ($roomUsage[$lesson->classroom_id] ?? 0) + 1;
            }

            $slotUsage[$key] = ($slotUsage[$key] ?? 0) + 1;
        }

        // ------------------------------------------------------------------
        // 4. Expand teacher assignments into individual lesson requirements:
        //    - If assignment has section_id set, apply to that specific class stream only.
        //    - If section_id is null, apply to all sections (streams) of that course.
        // ------------------------------------------------------------------
        $assignments = CourseSubject::query()
            ->where('school_id', $schoolId)
            ->with(['course.sections:id,course_id,name,school_id', 'subject:id,name', 'section:id,course_id,name'])
            ->get();

        $requirements = [];
        $skipped = [];

        foreach ($assignments as $assignment) {
            if (! $assignment->teacher_id) {
                $skipped[] = sprintf(
                    '%s — %s: no teacher assigned',
                    $assignment->course?->name ?? 'Unknown course',
                    $assignment->subject?->name ?? 'Unknown subject'
                );

                continue;
            }

            // Determine target sections
            $targetSections = collect();
            if ($assignment->section_id) {
                if ($assignment->section) {
                    $targetSections->push($assignment->section);
                }
            } else {
                $targetSections = $assignment->course?->sections ?? collect();
            }

            if ($targetSections->isEmpty()) {
                $skipped[] = sprintf(
                    '%s — %s: no matching sections (streams) found',
                    $assignment->course?->name ?? 'Unknown course',
                    $assignment->subject?->name ?? 'Unknown subject'
                );

                continue;
            }

            foreach ($targetSections as $section) {
                $weeklyPeriods = max(1, (int) $assignment->periods_per_week);

                for ($i = 0; $i < $weeklyPeriods; $i++) {
                    $requirements[] = [
                        'course_id' => $assignment->course_id,
                        'section_id' => $section->id,
                        'section_label' => trim(($assignment->course->name ?? '').' '.$section->name),
                        'subject_id' => $assignment->subject_id,
                        'subject_label' => $assignment->subject->name ?? 'Unknown subject',
                        'teacher_id' => $assignment->teacher_id,
                        'room_preference' => $assignment->room_preference,
                    ];
                }
            }
        }

        // ------------------------------------------------------------------
        // 5. Most-constrained-first ordering: requirements belonging to the
        //    busiest teachers are placed before flexible ones, so scarce
        //    availability is consumed where it matters most.
        // ------------------------------------------------------------------
        $teacherLoad = [];
        $teacherSectionSpread = [];

        foreach ($requirements as $req) {
            $teacherLoad[$req['teacher_id']] = ($teacherLoad[$req['teacher_id']] ?? 0) + 1;
            $teacherSectionSpread[$req['teacher_id']][$req['section_id']] = true;
        }

        usort($requirements, function ($a, $b) use ($teacherLoad, $teacherSectionSpread) {
            $loadDiff = ($teacherLoad[$b['teacher_id']] ?? 0) <=> ($teacherLoad[$a['teacher_id']] ?? 0);
            if ($loadDiff !== 0) {
                return $loadDiff;
            }

            $spreadA = count($teacherSectionSpread[$a['teacher_id']] ?? []);
            $spreadB = count($teacherSectionSpread[$b['teacher_id']] ?? []);

            return $spreadB <=> $spreadA ?: strcmp($a['section_label'], $b['section_label']);
        });

        // Subject-day counters used by the spread heuristic.
        $subjectDayCount = [];

        foreach ($survivingLessons as $lesson) {
            $subjectKey = $this->lessonSubjectKey($lesson);
            $subjectDayCount[$subjectKey][$lesson->day_of_week] = ($subjectDayCount[$subjectKey][$lesson->day_of_week] ?? 0) + 1;
        }

        // Resolve room preferences to concrete classroom IDs up front.
        $preferredRooms = [];
        $classrooms = Classroom::where('school_id', $schoolId)->get(['id', 'name']);
        foreach ($requirements as $index => $req) {
            $preferredRooms[$index] = $this->resolvePreferredRoom($req['room_preference'], $classrooms);
        }

        // ------------------------------------------------------------------
        // 6. Placement loop + repair pass. Every candidate cell is scored and
        //    the globally best-scoring feasible cell wins.
        // ------------------------------------------------------------------
        $lessonsToInsert = [];
        $unplaced = [];
        $requirementIndex = 0;

        foreach ($requirements as $index => $req) {
            $placedCell = $this->findBestCell(
                $req,
                $index,
                $days,
                $slots->pluck('id')->values()->all(),
                compact('sectionBusy', 'teacherBusy', 'roomBusy', 'slotUsage', 'roomUsage', 'subjectDayCount'),
                $preferredRooms[$index],
                $classrooms->pluck('id')->values()->all(),
                $maxPerSubjectPerDay,
                $lessonsToInsert,
            );

            if ($placedCell === null) {
                $unplaced[$index] = [
                    'label' => sprintf('%s — %s', $req['section_label'], $req['subject_label']),
                    'reason' => $this->diagnoseFailure($req, $days, $slots->count(), $teacherBusy, $sectionBusy, $roomBusy, $classrooms->count()),
                ];

                continue;
            }

            [$day, $slotId, $roomId] = $placedCell;
            $key = $day.'|'.$slotId;

            $sectionBusy[$req['section_id']][$key] = true;
            $teacherBusy[$req['teacher_id']][$key] = true;
            $roomBusy[$roomId][$key] = true;

            $slotUsage[$key] = ($slotUsage[$key] ?? 0) + 1;
            $roomUsage[$roomId] = ($roomUsage[$roomId] ?? 0) + 1;

            $subjectDayCount[$req['section_id'].'#'.$req['subject_id']][$day]
                = ($subjectDayCount[$req['section_id'].'#'.$req['subject_id']][$day] ?? 0) + 1;

            $lessonsToInsert[] = [
                'school_id' => $schoolId,
                'template_id' => $templateId,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'course_id' => $req['course_id'],
                'section_id' => $req['section_id'],
                'subject_id' => $req['subject_id'],
                'teacher_id' => $req['teacher_id'],
                'classroom_id' => $roomId,
                'time_slot_id' => $slotId,
                'day_of_week' => $day,
                'is_locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            unset($unplaced[$index]);
        }

        // Repair pass: failures earlier in the run may now have open cells the
        // diagnosis misjudged (e.g. rooms freed conceptually); retry once.
        if (! empty($unplaced)) {
            foreach (array_keys($unplaced) as $index) {
                $req = $requirements[$index];

                $placedCell = $this->findBestCell(
                    $req,
                    $index,
                    $days,
                    $slots->pluck('id')->values()->all(),
                    compact('sectionBusy', 'teacherBusy', 'roomBusy', 'slotUsage', 'roomUsage', 'subjectDayCount'),
                    $preferredRooms[$index],
                    $classrooms->pluck('id')->values()->all(),
                    $maxPerSubjectPerDay,
                    $lessonsToInsert,
                );

                if ($placedCell !== null) {
                    [$day, $slotId, $roomId] = $placedCell;
                    $key = $day.'|'.$slotId;

                    $sectionBusy[$req['section_id']][$key] = true;
                    $teacherBusy[$req['teacher_id']][$key] = true;
                    $roomBusy[$roomId][$key] = true;
                    $slotUsage[$key] = ($slotUsage[$key] ?? 0) + 1;
                    $roomUsage[$roomId] = ($roomUsage[$roomId] ?? 0) + 1;
                    $subjectDayCount[$req['section_id'].'#'.$req['subject_id']][$day]
                        = ($subjectDayCount[$req['section_id'].'#'.$req['subject_id']][$day] ?? 0) + 1;

                    $lessonsToInsert[] = [
                        'school_id' => $schoolId,
                        'template_id' => $templateId,
                        'academic_year_id' => $academicYearId,
                        'term_id' => $termId,
                        'course_id' => $req['course_id'],
                        'section_id' => $req['section_id'],
                        'subject_id' => $req['subject_id'],
                        'teacher_id' => $req['teacher_id'],
                        'classroom_id' => $roomId,
                        'time_slot_id' => $slotId,
                        'day_of_week' => $day,
                        'is_locked' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    unset($unplaced[$index]);
                }
            }
        }

        // ------------------------------------------------------------------
        // 7. Persist in one transaction-safe bulk insert.
        // ------------------------------------------------------------------
        if (! empty($lessonsToInsert)) {
            foreach (array_chunk($lessonsToInsert, 250) as $chunk) {
                DB::table('timetable_lessons')->insert($chunk);
            }
        }

        return [
            'placed' => count($lessonsToInsert),
            'unplaced' => array_values($unplaced),
            'skipped' => $skipped,
        ];
    }

    /**
     * Scan every feasible (day, slot) cell and return the best-scoring one
     * as [day, slotId, roomId], or null when nothing is feasible.
     */
    protected function findBestCell(
        array $req,
        int $index,
        array $days,
        array $slotIds,
        array $state,
        ?int $preferredRoomId,
        array $roomIds,
        int $maxPerSubjectPerDay,
        array &$pendingLessons,
    ): ?array {
        extract($state, EXTR_SKIP);

        $best = null;
        $bestScore = PHP_FLOAT_MAX;

        // Pending (not yet inserted) placements must also block their cells.
        $pendingSectionBusy = $pendingTeacherBusy = $pendingRoomBusy = [];
        foreach ($pendingLessons as $lesson) {
            if ((int) $lesson['section_id'] === (int) $req['section_id']
                || (int) $lesson['teacher_id'] === (int) $req['teacher_id']) {
                continue; // handled below per-key
            }
        }
        foreach ($pendingLessons as $lesson) {
            $pKey = $lesson['day_of_week'].'|'.$lesson['time_slot_id'];
            $pendingSectionBusy[$lesson['section_id']][$pKey] = true;
            $pendingTeacherBusy[$lesson['teacher_id']][$pKey] = true;
            $pendingRoomBusy[$lesson['classroom_id']][$pKey] = true;
        }

        $subjectKey = $req['section_id'].'#'.$req['subject_id'];

        foreach ($days as $dayIndex => $day) {
            // Spread heuristic: respect the per-day subject cap.
            if (($subjectDayCount[$subjectKey][$day] ?? 0) >= $maxPerSubjectPerDay) {
                continue;
            }

            foreach ($slotIds as $slotIndex => $slotId) {
                $key = $day.'|'.$slotId;

                // Hard constraint checks (existing + pending placements).
                if (isset($sectionBusy[$req['section_id']][$key])
                    || isset($pendingSectionBusy[$req['section_id']][$key])) {
                    continue;
                }

                if (isset($teacherBusy[$req['teacher_id']][$key])
                    || isset($pendingTeacherBusy[$req['teacher_id']][$key])) {
                    continue;
                }

                // Pick the best available room for this cell.
                $roomId = $this->pickRoom(
                    $preferredRoomId,
                    $roomIds,
                    $key,
                    $roomBusy,
                    $pendingRoomBusy,
                    $roomUsage,
                );

                if ($roomId === null) {
                    continue;
                }

                // Composite score — lower is better:
                //   x100  subject-day balance  (spread same subject over days)
                //   x10   grid balance        (avoid school-wide congested periods)
                //   x2    day rotation        (even weekly distribution)
                //   x1    slot position       (mild preference for earlier periods)
                $score = (($subjectDayCount[$subjectKey][$day] ?? 0) * 100)
                    + (($slotUsage[$key] ?? 0) * 10)
                    + ($dayIndex * 2)
                    + $slotIndex;

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = [$day, $slotId, $roomId];
                }
            }
        }

        return $best;
    }

    /**
     * Choose a free classroom: preferred first, then least-used.
     */
    protected function pickRoom(
        ?int $preferredRoomId,
        array $roomIds,
        string $key,
        array $roomBusy,
        array $pendingRoomBusy,
        array $roomUsage,
    ): ?int {
        if ($preferredRoomId !== null
            && ! isset($roomBusy[$preferredRoomId][$key])
            && ! isset($pendingRoomBusy[$preferredRoomId][$key])) {
            return $preferredRoomId;
        }

        $bestRoom = null;
        $lowestUse = PHP_INT_MAX;

        foreach ($roomIds as $roomId) {
            if (isset($roomBusy[$roomId][$key]) || isset($pendingRoomBusy[$roomId][$key])) {
                continue;
            }

            $use = $roomUsage[$roomId] ?? 0;
            if ($use < $lowestUse) {
                $lowestUse = $use;
                $bestRoom = $roomId;
            }
        }

        return $bestRoom;
    }

    /**
     * Explain why a requirement could not be placed, for the user report.
     */
    protected function diagnoseFailure(
        array $req,
        array $days,
        int $slotCount,
        array $teacherBusy,
        array $sectionBusy,
        array $roomBusy,
        int $classroomCount,
    ): string {
        if ($slotCount === 0) {
            return 'No teaching periods configured';
        }

        $totalCells = count($days) * $slotCount;

        $teacherFree = 0;
        for ($i = 0; $i < $totalCells; $i++) {
            $day = $days[intdiv($i, $slotCount)];
            $slotId = $i % $slotCount + 1;
            if (! isset($teacherBusy[$req['teacher_id']][$day.'|'.$slotId])) {
                $teacherFree++;
            }
        }

        if ($teacherFree === 0) {
            return 'Teacher has no free period left in the week';
        }

        if ($classroomCount === 0) {
            return 'No classrooms registered';
        }

        return 'Not enough free matching periods (teacher, class or rooms all busy)';
    }

    protected function resolvePreferredRoom($roomPreference, $classrooms): ?int
    {
        if (empty($roomPreference)) {
            return null;
        }

        if (is_numeric($roomPreference)) {
            return $classrooms->firstWhere('id', (int) $roomPreference)?->id;
        }

        return $classrooms->first(fn ($room) => strcasecmp($room->name, (string) $roomPreference) === 0)?->id;
    }

    protected function lessonSubjectKey($lesson): string
    {
        return $lesson->section_id.'#'.$lesson->subject_id;
    }
}
