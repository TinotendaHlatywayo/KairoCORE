<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Subject;

class PurgeSubjects extends Command
{
    protected $signature = 'schoolcore:purge-subjects
        {school : School ID whose subjects should be trimmed}
        {--codes= : Comma-separated subject codes to remove, e.g. COMSCI,BUS,ACC}
        {--keep= : Comma-separated subject codes to KEEP; every other subject in the school is removed}
        {--force : Remove subjects even when locked timetable lessons reference them}
        {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Permanently remove unwanted subjects (and only their teacher-assignment / timetable-lesson references) from a school. Refuses to touch any subject that still has assessment, mark, report or homework data, so academic records are never harmed.';

    /**
     * Tables that hold graded/curriculum records. If any of these reference a
     * subject, the subject is protected and reported instead of removed.
     *
     * @var array<int, string>
     */
    private const PROTECTED_TABLES = [
        'assessment_marks',
        'assessment_types',
        'assessment_plans',
        'digital_assessments',
        'homeworks',
        'learner_mastery',
        'mark_records',
        'question_bank',
        'screening_rules',
        'subject_papers',
    ];

    /**
     * References that exist purely because the subject was assigned/scheduled.
     * These are what make stale subjects resurface in the timetable builder.
     *
     * @var array<int, string>
     */
    private const CLEANABLE_TABLES = [
        'course_subject',
        'timetable_lessons',
    ];

    public function handle(): int
    {
        $schoolId = (int) $this->argument('school');
        $codes = $this->parseList($this->option('codes'));
        $keep = $this->parseList($this->option('keep'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($codes !== [] && $keep !== []) {
            $this->error('Use either --codes or --keep, not both.');

            return self::FAILURE;
        }

        if ($codes === [] && $keep === []) {
            $this->error('Specify --codes=COMA,B or --keep=CODA,B.');

            return self::FAILURE;
        }

        $school = School::query()->withoutGlobalScopes()->find($schoolId);

        if ($school === null) {
            $this->error("No school found with ID {$schoolId}.");

            return self::FAILURE;
        }

        $subjects = Subject::query()
            ->withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->get();

        if ($keep !== []) {
            $candidates = $subjects
                ->filter(fn (Subject $s) => ! in_array(strtoupper((string) $s->code), $keep, true))
                ->values();
        } else {
            $upper = array_map('strtoupper', $codes);
            $candidates = $subjects
                ->filter(fn (Subject $s) => in_array(strtoupper((string) $s->code), $upper, true))
                ->values();
        }

        if ($candidates->isEmpty()) {
            $this->info("School {$schoolId}: no matching subjects to remove.");

            return self::SUCCESS;
        }

        $this->line(sprintf(
            'School %d (%s): %d subject(s) matched for removal.',
            $schoolId,
            $school->name,
            $candidates->count()
        ));

        $removed = 0;
        $protected = 0;
        $skippedLocked = 0;

        foreach ($candidates as $subject) {
            $sid = (int) $subject->id;

            $protectedCount = $this->countProtectedReferences($sid);
            if ($protectedCount > 0) {
                $protected++;
                $this->warn(sprintf(
                    '  SKIP %s (%s): still referenced by %d record(s) in assessment/mark/report tables. Existing academic data is never removed.',
                    $subject->name,
                    $subject->code,
                    $protectedCount
                ));

                continue;
            }

            $lockedLessons = (int) DB::table('timetable_lessons')
                ->where('school_id', $schoolId)
                ->where('subject_id', $sid)
                ->where('is_locked', true)
                ->count();

            if ($lockedLessons > 0 && ! $force) {
                $skippedLocked++;
                $this->warn(sprintf(
                    '  SKIP %s (%s): %d locked timetable lesson(s) reference this subject. Re-run with --force to remove them too.',
                    $subject->name,
                    $subject->code,
                    $lockedLessons
                ));

                continue;
            }

            $planned = $this->planRemoval($schoolId, $sid);
            $this->line(sprintf(
                '  REMOVE %s (%s): %d teacher assignment(s), %d timetable lesson(s).',
                $subject->name,
                $subject->code,
                $planned['course_subject'],
                $planned['timetable_lessons']
            ));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($schoolId, $sid): void {
                DB::table('course_subject')
                    ->where('school_id', $schoolId)
                    ->where('subject_id', $sid)
                    ->delete();

                DB::table('timetable_lessons')
                    ->where('school_id', $schoolId)
                    ->where('subject_id', $sid)
                    ->delete();

                Subject::query()
                    ->withoutGlobalScopes()
                    ->whereKey($sid)
                    ->delete();
            });

            $removed++;
        }

        if ($dryRun) {
            $this->info('[dry-run] no changes written.');
        } else {
            $this->info(sprintf(
                'Done. Removed %d subject(s); %d protected by academic data; %d skipped due to locked lessons.',
                $removed,
                $protected,
                $skippedLocked
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Sum every protected-table row that references the subject.
     */
    private function countProtectedReferences(int $subjectId): int
    {
        $total = 0;

        foreach (self::PROTECTED_TABLES as $table) {
            $total += (int) DB::table($table)->where('subject_id', $subjectId)->count();
        }

        return $total;
    }

    /**
     * @return array{course_subject: int, timetable_lessons: int}
     */
    private function planRemoval(int $schoolId, int $subjectId): array
    {
        return [
            'course_subject' => (int) DB::table('course_subject')
                ->where('school_id', $schoolId)
                ->where('subject_id', $subjectId)
                ->count(),
            'timetable_lessons' => (int) DB::table('timetable_lessons')
                ->where('school_id', $schoolId)
                ->where('subject_id', $subjectId)
                ->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $code) => strtoupper(trim($code)),
            explode(',', $value)
        )));
    }
}
