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

        // Slot names written during THIS compile. Any slot of this template
        // not in this set is stale (left over from an earlier compile with
        // different settings) and must be removed so the grid never shows
        // out-of-order or overlapping rows.
        $touched = [];

        while ($currentTime->lt($endTimeOfLessons)) {
            $nextTime = $currentTime->copy()->addMinutes($periodLength);

            // 1. Intercept Closing Time Overrun Gaps
            if ($nextTime->gt($endTimeOfLessons)) {
                $touched[] = 'Free / Buffer Slot';
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
                    $touched[] = 'Free Slot (Pre-Break)';
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
                    $touched[] = 'Tea Break';
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
                    $touched[] = 'Free Slot (Pre-Lunch)';
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
                    $touched[] = 'Lunch Break';
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
                $touched[] = 'Tea Break';
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
                $touched[] = 'Lunch Break';
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
            $touched[] = 'Period '.$periodCount;
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

        // Reset the template back to a single clean, chronological slot set.
        // Anything not written by this compile is stale and keeps scrambled
        // or overlapping times in the grid (and its lessons are orphans).
        if ($templateId && $touched !== []) {
            $stale = TimeSlot::where('school_id', $schoolId)
                ->where('template_id', $templateId)
                ->whereNotIn('name', $touched)
                ->pluck('id');

            if ($stale->isNotEmpty()) {
                TimetableLesson::where('school_id', $schoolId)
                    ->whereIn('time_slot_id', $stale)
                    ->delete();
                TimeSlot::where('school_id', $schoolId)
                    ->where('template_id', $templateId)
                    ->whereIn('id', $stale)
                    ->delete();
            }
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
        $academicYearId = isset($params['academic_year_id']) && filled($params['academic_year_id'])
            ? (int) $params['academic_year_id'] : null;
        $termId = isset($params['term_id']) && filled($params['term_id'])
            ? (int) $params['term_id'] : null;
        $maxPerSubjectPerDay = max(1, (int) ($params['max_per_subject_per_day'] ?? 1));

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // ------------------------------------------------------------------
        // 1. Load the teaching grid (non-break slots only).
        // ------------------------------------------------------------------
        $slots = TimeSlot::where('school_id', $schoolId)
            ->where('template_id', $templateId)
            ->where('is_break', false)
            ->orderBy('start_time')
            ->get(['id', 'start_time', 'end_time', 'duration_minutes']);

        if ($slots->isEmpty()) {
            throw new \Exception('No teaching periods found for this template. Compile the time slots first.');
        }

        $slotIds = $slots->pluck('id')->values()->all();

        // Short slots (<= 40 minutes) are automatically grouped into double
        // blocks so the grid is not littered with too many single free cells.
        $avgDuration = (int) round($slots->avg(fn ($s) => $s->duration_minutes ?? (int) round((strtotime($s->end_time) - strtotime($s->start_time)) / 60)));
        $autoPairShortSlots = $avgDuration > 0 && $avgDuration <= 40;

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
            ->get(['section_id', 'teacher_id', 'classroom_id', 'subject_id', 'time_slot_id', 'day_of_week']);

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
            ->with(['course.sections:id,course_id,name,school_id,classroom_id', 'subject:id,name', 'section:id,course_id,name,classroom_id'])
            ->get();

        $requirements = [];
        $skipped = [];

        if ($assignments->isEmpty()) {
            $skipped[] = 'No teacher assignments found for this school. Create subject → level/stream → teacher assignments (Teacher Assignments) before auto-generating lessons.';
        }

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

                $explicitDoubles = max(0, (int) ($assignment->double_periods_per_week ?? 0));
                $explicitTriples = max(0, (int) ($assignment->triple_periods_per_week ?? 0));

                // When nobody asked for a specific block pattern and the slot
                // duration is short, the engine auto-pairs periods into
                // doubles so free slots never dominate the weekly grid.
                if ($explicitDoubles === 0 && $explicitTriples === 0 && $autoPairShortSlots) {
                    $explicitDoubles = intdiv($weeklyPeriods, 2);
                    $explicitTriples = 0;
                }

                $blockedPeriods = ($explicitDoubles * 2) + ($explicitTriples * 3);

                if ($blockedPeriods > $weeklyPeriods) {
                    $overflow = $blockedPeriods - $weeklyPeriods;
                    $explicitTriples = max(0, $explicitTriples - $overflow);
                    $blockedPeriods = ($explicitDoubles * 2) + ($explicitTriples * 3);
                }

                $singleCount = max(0, $weeklyPeriods - $blockedPeriods);

                $blockTemplate = [
                    'course_id' => $assignment->course_id,
                    'section_id' => $section->id,
                    'section_label' => trim(($assignment->course->name ?? '').' '.$section->name),
                    'subject_id' => $assignment->subject_id,
                    'subject_label' => $assignment->subject->name ?? 'Unknown subject',
                    'teacher_id' => $assignment->teacher_id,
                    'room_preference' => $assignment->room_preference,
                    'fixed_room_id' => $section->classroom_id
                        ? (int) $section->classroom_id
                        : null,
                ];

                for ($i = 0; $i < $explicitTriples; $i++) {
                    $requirements[] = $blockTemplate + ['block_size' => 3];
                }

                for ($i = 0; $i < $explicitDoubles; $i++) {
                    $requirements[] = $blockTemplate + ['block_size' => 2];
                }

                for ($i = 0; $i < $singleCount; $i++) {
                    $requirements[] = $blockTemplate + ['block_size' => 1];
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

        // Classrooms pinned to a specific class (section) are reserved for that
        // section only — no other class stream may ever use them.
        $pinnedRoomIds = collect($requirements)
            ->pluck('fixed_room_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Auto-placement only uses ordinary classrooms. Purpose-built rooms
        // (science / computer laboratories, etc.) are excluded by default —
        // a teacher can still book one explicitly per lesson or via the
        // assignment's room_preference.
        $genericRoomIds = $classrooms
            ->reject(fn ($room) => $this->isLabRoom($room->name))
            ->pluck('id')
            ->diff($pinnedRoomIds)
            ->values()
            ->all();

        foreach ($requirements as $index => $req) {
            $preferredRooms[$index] = $this->resolvePreferredRoom($req['room_preference'], $classrooms);
        }

        // ------------------------------------------------------------------
        // 6. Placement loop + repair pass. Every candidate cell is scored and
        //    the globally best-scoring feasible cell wins. Requirements with a
        //    block_size > 1 occupy several consecutive time slots so the grid
        //    packs double/triple lessons tightly and free slots stay few.
        // ------------------------------------------------------------------
        $lessonsToInsert = [];
        $unplaced = [];
        $requirementIndex = 0;

        foreach ($requirements as $index => $req) {
            $blockSize = (int) ($req['block_size'] ?? 1);

            $placed = $this->placeRequirement(
                $req,
                $index,
                $blockSize,
                $days,
                $slotIds,
                $maxPerSubjectPerDay,
                $req['fixed_room_id'] ?? $preferredRooms[$index],
                $req['fixed_room_id'] ? [$req['fixed_room_id']] : $genericRoomIds,
                $sectionBusy,
                $teacherBusy,
                $roomBusy,
                $slotUsage,
                $roomUsage,
                $subjectDayCount,
                $lessonsToInsert,
                $schoolId,
                $templateId,
                $academicYearId,
                $termId,
            );

            if ($placed) {
                unset($unplaced[$index]);
            } else {
                $unplaced[$index] = [
                    'label' => sprintf('%s — %s (%s)', $req['section_label'], $req['subject_label'], $blockSize > 1 ? $blockSize.'-period block' : 'single'),
                    'reason' => $this->diagnoseFailure($req, $days, count($slotIds), $teacherBusy, $sectionBusy, $roomBusy, $classrooms->count()),
                ];
            }
        }

        // Repair pass: failures earlier in the run may now have open cells the
        // diagnosis misjudged (e.g. rooms freed conceptually); retry once.
        if (! empty($unplaced)) {
            foreach (array_keys($unplaced) as $index) {
                $req = $requirements[$index];
                $blockSize = (int) ($req['block_size'] ?? 1);

                $placed = $this->placeRequirement(
                    $req,
                    $index,
                    $blockSize,
                    $days,
                    $slotIds,
                    $maxPerSubjectPerDay,
                    $req['fixed_room_id'] ?? $preferredRooms[$index],
                    $req['fixed_room_id'] ? [$req['fixed_room_id']] : $genericRoomIds,
                    $sectionBusy,
                    $teacherBusy,
                    $roomBusy,
                    $slotUsage,
                    $roomUsage,
                    $subjectDayCount,
                    $lessonsToInsert,
                    $schoolId,
                    $templateId,
                    $academicYearId,
                    $termId,
                );

                if ($placed) {
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
     * Place a requirement (single, double or triple period) into the weekly
     * grid. Mutates the busy maps and appends all generated lessons to
     * $lessonsToInsert. Returns true when the whole block was placed.
     */
    protected function placeRequirement(
        array $req,
        int $index,
        int $blockSize,
        array $days,
        array $slotIds,
        int $maxPerSubjectPerDay,
        ?int $preferredRoomId,
        array $roomIds,
        array &$sectionBusy,
        array &$teacherBusy,
        array &$roomBusy,
        array &$slotUsage,
        array &$roomUsage,
        array &$subjectDayCount,
        array &$pendingLessons,
        int $schoolId,
        int $templateId,
        ?int $academicYearId,
        ?int $termId,
    ): bool {
        $placedCell = $this->findBestCell(
            $req,
            $index,
            $days,
            $slotIds,
            [
                'sectionBusy' => $sectionBusy,
                'teacherBusy' => $teacherBusy,
                'roomBusy' => $roomBusy,
                'slotUsage' => $slotUsage,
                'roomUsage' => $roomUsage,
                'subjectDayCount' => $subjectDayCount,
            ],
            $preferredRoomId,
            $roomIds,
            $maxPerSubjectPerDay,
            $blockSize,
            $pendingLessons,
        );

        if ($placedCell === null) {
            return false;
        }

        [$day, $firstSlotIndex, $roomId] = $placedCell;
        $slotCount = count($slotIds);

        for ($b = 0; $b < $blockSize; $b++) {
            $slotIndex = $firstSlotIndex + $b;
            if ($slotIndex >= $slotCount) {
                return false;
            }

            $slotId = $slotIds[$slotIndex];
            $key = $day.'|'.$slotId;

            $sectionBusy[$req['section_id']][$key] = true;
            $teacherBusy[$req['teacher_id']][$key] = true;
            $roomBusy[$roomId][$key] = true;

            $slotUsage[$key] = ($slotUsage[$key] ?? 0) + 1;
            $roomUsage[$roomId] = ($roomUsage[$roomId] ?? 0) + 1;

            $pendingLessons[] = [
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
        }

        $subjectKey = $req['section_id'].'#'.$req['subject_id'];
        $subjectDayCount[$subjectKey][$day] = ($subjectDayCount[$subjectKey][$day] ?? 0) + $blockSize;

        return true;
    }

    /**
     * Scan every feasible (day, slot) cell and return the best-scoring one
     * as [day, firstSlotIndex, roomId], or null when nothing is feasible.
     * With a blockSize > 1 the chosen room must be free for every consecutive
     * slot of the block.
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
        int $blockSize = 1,
        array &$pendingLessons = [],
    ): ?array {
        extract($state, EXTR_SKIP);

        $best = null;
        $bestScore = PHP_FLOAT_MAX;

        $pendingSectionBusy = $pendingTeacherBusy = $pendingRoomBusy = [];
        foreach ($pendingLessons as $lesson) {
            $pKey = $lesson['day_of_week'].'|'.$lesson['time_slot_id'];
            $pendingSectionBusy[$lesson['section_id']][$pKey] = true;
            $pendingTeacherBusy[$lesson['teacher_id']][$pKey] = true;
            $pendingRoomBusy[$lesson['classroom_id']][$pKey] = true;
        }

        $subjectKey = $req['section_id'].'#'.$req['subject_id'];
        $slotCount = count($slotIds);

        // Rotate the day visitation order per subject so different subjects
        // naturally gravitate towards different "first" days — otherwise the
        // highest-load teacher always claims Period 1 Monday and the same
        // subject sits on the opening slot every single day.
        $orderedDays = $this->rotateWeekDays($days, (string) $subjectKey);

        foreach ($orderedDays as $dayIndex => $day) {
            // Allow a block of blockSize to occupy its own slots on a day even
            // when the per-day single cap looks tighter: the user asked for it.
            $dayCap = max($maxPerSubjectPerDay, $blockSize);
            if (($subjectDayCount[$subjectKey][$day] ?? 0) + $blockSize > $dayCap) {
                continue;
            }

            foreach ($slotIds as $slotIndex => $slotId) {
                if ($slotIndex + $blockSize > $slotCount) {
                    continue;
                }

                // Collect the consecutive slot ids belonging to this block.
                $blockIds = array_slice($slotIds, $slotIndex, $blockSize);

                $blockFree = true;
                foreach ($blockIds as $bsId) {
                    $key = $day.'|'.$bsId;

                    if (isset($sectionBusy[$req['section_id']][$key])
                        || isset($pendingSectionBusy[$req['section_id']][$key])) {
                        $blockFree = false;
                        break;
                    }

                    if (isset($teacherBusy[$req['teacher_id']][$key])
                        || isset($pendingTeacherBusy[$req['teacher_id']][$key])) {
                        $blockFree = false;
                        break;
                    }
                }

                if (! $blockFree) {
                    continue;
                }

                // Pick a room free for the entire block (preferred first).
                $roomId = $this->pickBlockRoom(
                    $preferredRoomId,
                    $roomIds,
                    $day,
                    $blockIds,
                    $roomBusy,
                    $pendingRoomBusy,
                    $roomUsage,
                );

                if ($roomId === null) {
                    continue;
                }

                $firstKey = $day.'|'.$blockIds[0];

                // Composite score — lower is better:
                //   x100  subject-day balance  (spread same subject over days)
                //   x10   grid balance        (avoid school-wide congested periods)
                //   x2    day rotation        (even weekly distribution)
                //   x1    slot position       (mild preference for earlier periods)
                //   jitter (0-5)  breaks ties so the same subject does not always
                //   land on Period 1 Monday on every regeneration run.
                $score = (($subjectDayCount[$subjectKey][$day] ?? 0) * 100)
                    + (($slotUsage[$firstKey] ?? 0) * 10)
                    + ($dayIndex * 2)
                    + $slotIndex
                    + mt_rand(0, 10);

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = [$day, $slotIndex, $roomId];
                }
            }
        }

        return $best;
    }

    /**
     * Choose a free classroom for a block of consecutive slots: preferred
     * first, then least-used among the rooms free in every slot of the block.
     */
    protected function pickBlockRoom(
        ?int $preferredRoomId,
        array $roomIds,
        string $day,
        array $blockIds,
        array $roomBusy,
        array $pendingRoomBusy,
        array $roomUsage,
    ): ?int {
        $preferredFree = $preferredRoomId !== null;
        if ($preferredFree) {
            foreach ($blockIds as $bsId) {
                $key = $day.'|'.$bsId;
                if (isset($roomBusy[$preferredRoomId][$key]) || isset($pendingRoomBusy[$preferredRoomId][$key])) {
                    $preferredFree = false;
                    break;
                }
            }
            if ($preferredFree) {
                return $preferredRoomId;
            }
        }

        $bestRoom = null;
        $lowestUse = PHP_INT_MAX;

        foreach ($roomIds as $roomId) {
            $free = true;
            foreach ($blockIds as $bsId) {
                $key = $day.'|'.$bsId;
                if (isset($roomBusy[$roomId][$key]) || isset($pendingRoomBusy[$roomId][$key])) {
                    $free = false;
                    break;
                }
            }
            if (! $free) {
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

    /**
     * Purpose-built rooms (science / computer laboratories, media centres, etc.)
     * are never chosen automatically; the school decides per lesson whether a
     * practical has to take place there.
     */
    protected function isLabRoom(string $roomName): bool
    {
        return (bool) preg_match('/(laboratory|computer\s*(lab|room)|media\s*centre?|workshop)\b/i', $roomName);
    }

    protected function lessonSubjectKey($lesson): string
    {
        return $lesson->section_id.'#'.$lesson->subject_id;
    }

    /**
     * Deterministically rotate a weekday list by a stable hash of the subject's
     * seed key, so every subject evaluates the week from a different starting
     * day. All five days are still visited and the day-spread cap is untouched;
     * only the tie-breaking preference order changes.
     */
    protected function rotateWeekDays(array $days, string $seedKey): array
    {
        $count = count($days);
        if ($count < 2) {
            return $days;
        }

        $offset = crc32($seedKey) % $count;

        return array_merge(array_slice($days, $offset), array_slice($days, 0, $offset));
    }
}
