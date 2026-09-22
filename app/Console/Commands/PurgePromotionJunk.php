<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Modules\Promotion\Models\PromotionItem;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class PurgePromotionJunk extends Command
{
    protected $signature = 'schoolcore:purge-promotion-junk {school? : Optional school ID. Defaults to every school.} {--dry-run : Report what would be deleted without deleting anything.}';

    protected $description = 'Permanently remove junk students (blank names, INTAKE-* demo intake rows, or soft-deleted records) and their orphaned promotion items / enrollments so stale and fake students stop appearing in previews and runs.';

    public function handle(): int
    {
        $schoolId = $this->argument('school');
        $dryRun = (bool) $this->option('dry-run');

        $schools = School::query()
            ->when($schoolId, fn ($q) => $q->whereKey((int) $schoolId))
            ->get();

        if ($schools->isEmpty()) {
            $this->warn('No matching schools found.');

            return self::SUCCESS;
        }

        $totals = ['items' => 0, 'enrollments' => 0, 'students' => 0];

        foreach ($schools as $school) {
            $sid = (int) $school->id;

            $junkStudentIds = Student::withoutGlobalScopes()
                ->withTrashed()
                ->where('school_id', $sid)
                ->where(function ($q) {
                    $q->whereNull('first_name')->orWhere('first_name', '')
                        ->orWhereNull('last_name')->orWhere('last_name', '')
                        ->orWhere('student_id_number', 'like', 'INTAKE-%');
                })
                ->pluck('id');

            // Soft-deleted students are invisible everywhere except stale
            // promotion runs, so their items are removed too (the student
            // themselves stay soft-deleted unless they are junk).
            $trashedStudentIds = Student::withoutGlobalScopes()
                ->onlyTrashed()
                ->where('school_id', $sid)
                ->pluck('id');

            $badIds = $junkStudentIds->merge($trashedStudentIds)->unique()->values()->all();

            if ($badIds === []) {
                $this->info("School {$sid}: clean, nothing to purge.");

                continue;
            }

            $itemCount = PromotionItem::withoutGlobalScopes()
                ->where('school_id', $sid)
                ->whereIn('student_id', $badIds)
                ->count();

            $enrollmentCount = Enrollment::withoutGlobalScopes()
                ->where('school_id', $sid)
                ->whereIn('student_id', $badIds)
                ->count();

            $studentCount = $junkStudentIds->count();

            $this->line(sprintf(
                'School %d: %d stale promotion item(s), %d orphan enrollment(s), %d junk student(s).',
                $sid,
                $itemCount,
                $enrollmentCount,
                $studentCount
            ));

            if ($dryRun) {
                continue;
            }

            PromotionItem::withoutGlobalScopes()
                ->where('school_id', $sid)
                ->whereIn('student_id', $badIds)
                ->forceDelete();

            Enrollment::withoutGlobalScopes()
                ->where('school_id', $sid)
                ->whereIn('student_id', $badIds)
                ->forceDelete();

            Student::withoutGlobalScopes()
                ->whereIn('id', $junkStudentIds)
                ->forceDelete();

            $totals['items'] += $itemCount;
            $totals['enrollments'] += $enrollmentCount;
            $totals['students'] += $studentCount;
        }

        if ($dryRun) {
            $this->info('[dry-run] no changes written.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Done. Purged %d promotion item(s), %d enrollment(s) and %d student(s) across %d school(s).',
            $totals['items'],
            $totals['enrollments'],
            $totals['students'],
            $schools->count()
        ));

        return self::SUCCESS;
    }
}