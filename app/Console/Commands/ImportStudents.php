<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\StudentCsvService;
use Illuminate\Console\Command;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;

class ImportStudents extends Command
{
    protected $signature = 'students:import
        {folder : Folder containing the spreadsheets, or the root of per-school folders when using --all}
        {--school= : School tenant ID to import into (defaults to the first school)}
        {--all : Import every school from its own subfolder under the root}';

    protected $description = 'Import student spreadsheets for one school or every tenant, creating missing class streams as needed.';

    public function handle(): int
    {
        $folder = (string) $this->argument('folder');

        if (! is_dir($folder)) {
            $this->error("Directory not found: {$folder}");

            return Command::FAILURE;
        }

        if ($this->option('all')) {
            return $this->importAllSchools($folder);
        }

        $schoolId = (int) ($this->option('school') ?? School::query()->orderBy('id')->value('id'));

        if ($schoolId <= 0) {
            $this->error('No schools found.');

            return Command::FAILURE;
        }

        $school = School::find($schoolId);

        if (! $school) {
            $this->error("School ID {$schoolId} not found.");

            return Command::FAILURE;
        }

        $decisions = [];
        $totals = $this->importSchoolFolder($folder, $school, $decisions);

        return $this->summarise([$school->name => $totals]);
    }

    protected function importAllSchools(string $root): int
    {
        $schools = School::query()->orderBy('id')->get();

        if ($schools->isEmpty()) {
            $this->error('No schools found.');

            return Command::FAILURE;
        }

        $decisions = [];
        $summaries = [];

        foreach ($schools as $school) {
            $folder = $this->folderForSchool($root, $school);

            if ($folder === null) {
                $this->warn("Skipping {$school->name} (ID: {$school->id}): no matching subfolder in {$root}.");

                continue;
            }

            $this->info("Using folder {$folder} for {$school->name} (ID: {$school->id}).");
            $this->newLine();

            $summaries[$school->name] = $this->importSchoolFolder($folder, $school, $decisions);
        }

        if ($summaries === []) {
            $this->error("No school subfolders matched under {$root}. Name each folder after the school (ID, subdomain, login code, or name).");

            return Command::FAILURE;
        }

        return $this->summarise($summaries);
    }

    /**
     * @param  array<string, bool>  $decisions
     * @return array{success: int, skipped: int, failed: int}
     */
    protected function importSchoolFolder(string $folder, School $school, array &$decisions): array
    {
        $files = $this->spreadsheetFiles($folder);
        $totals = ['success' => 0, 'skipped' => 0, 'failed' => 0];

        if ($files === []) {
            $this->error("No .xlsx, .xls or .csv files found in {$folder}");

            return $totals;
        }

        $this->info("Importing student files for school: {$school->name} (ID: {$school->id})");
        $this->newLine();

        StudentCsvService::$missingStreamHandler = function (int $tenantId, string $courseName, string $sectionName) use ($school, &$decisions): bool {
            $key = $tenantId.'|'.StudentCsvService::streamKey($courseName, $sectionName);

            if (array_key_exists($key, $decisions)) {
                return $decisions[$key];
            }

            $this->newLine();
            $this->warn("New Class Stream detected: Grade [{$courseName}], Stream [{$sectionName}] is not registered in {$school->name}.");

            if (! $this->confirm('Do you want to create this class stream now?', true)) {
                return $decisions[$key] = false;
            }

            $level = str_contains(mb_strtolower($courseName), 'form')
                ? (preg_match('/form\s*([12])\b/i', $courseName) === 1 ? 'lower_secondary' : 'upper_secondary')
                : 'primary';

            $course = Course::withoutTenantScope()->firstOrCreate(
                ['school_id' => $tenantId, 'name' => $courseName],
                ['level' => $level],
            );

            Section::withoutTenantScope()->firstOrCreate(
                ['school_id' => $tenantId, 'course_id' => $course->id, 'name' => $sectionName],
                ['capacity' => 40],
            );

            $this->info("Successfully created Grade [{$courseName}] and Stream [{$sectionName}]. Continuing import...");

            return $decisions[$key] = true;
        };

        try {
            foreach ($files as $file) {
                $filename = basename($file);
                $this->info("Processing: {$filename}");

                try {
                    $filePath = StudentCsvService::resolveTempFilePath($file);
                    $headers = StudentCsvService::readCsvHeaders($filePath);

                    if ($headers === []) {
                        $this->warn('  -> Skipped: No readable headers.');

                        continue;
                    }

                    $map = StudentCsvService::guessMapping($headers);
                    $result = StudentCsvService::import($filePath, $school->id, $map);

                    $totals['success'] += $result['success'];
                    $totals['skipped'] += (int) ($result['skipped'] ?? 0);
                    $totals['failed'] += count($result['failures']);

                    $this->info("  -> Imported: {$result['success']} students successfully. Skipped (already registered): ".($result['skipped'] ?? 0).'. Failures: '.count($result['failures']));

                    if (! empty($result['failures'])) {
                        foreach (array_slice($result['failures'], 0, 3) as $fail) {
                            $this->line("     Row {$fail['row']}: ".implode('; ', $fail['errors']));
                        }

                        if (count($result['failures']) > 3) {
                            $this->line('     ... and '.(count($result['failures']) - 3).' more failures.');
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("  -> Error processing {$filename}: ".$e->getMessage());
                }

                $this->newLine();
            }
        } finally {
            StudentCsvService::$missingStreamHandler = null;
        }

        return $totals;
    }

    /** @return array<int, string> */
    protected function spreadsheetFiles(string $folder): array
    {
        $files = array_merge(
            glob("{$folder}/*.xlsx") ?: [],
            glob("{$folder}/*.xls") ?: [],
            glob("{$folder}/*.csv") ?: [],
        );

        if ($files === []) {
            $files = glob("{$folder}/csv/*.csv") ?: [];
        }

        return collect($files)
            ->unique(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    protected function folderForSchool(string $root, School $school): ?string
    {
        $normalise = static fn (string $value): string => preg_replace('/[^a-z0-9]/', '', mb_strtolower($value)) ?? '';

        $identifiers = collect([$school->id, $school->subdomain, $school->login_code])
            ->filter()
            ->map(fn ($value): string => mb_strtolower((string) $value))
            ->all();

        $schoolName = $normalise((string) $school->name);
        $matches = [];

        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $base = basename($directory);
            $normalisedBase = $normalise($base);

            if ($normalisedBase === '') {
                continue;
            }

            $exact = in_array(mb_strtolower($base), $identifiers, true);
            $fuzzy = $schoolName !== ''
                && (str_contains($schoolName, $normalisedBase) || str_contains($normalisedBase, $schoolName));

            if ($exact || $fuzzy) {
                $matches[] = $directory;
            }
        }

        if (count($matches) > 1) {
            $this->warn("Multiple folders match {$school->name}: ".implode(', ', $matches).'. Skipping to avoid importing the wrong data.');

            return null;
        }

        return $matches[0] ?? null;
    }

    /**
     * @param  array<string, array{success: int, skipped: int, failed: int}>  $summaries
     */
    protected function summarise(array $summaries): int
    {
        $this->info('=== Student Import Completed ===');

        foreach ($summaries as $school => $totals) {
            $this->info("{$school}: imported {$totals['success']}, skipped {$totals['skipped']}, failed {$totals['failed']}");
        }

        $this->info('Total successfully imported: '.array_sum(array_column($summaries, 'success')));
        $this->info('Total skipped (already registered): '.array_sum(array_column($summaries, 'skipped')));
        $this->info('Total failed rows: '.array_sum(array_column($summaries, 'failed')));

        return Command::SUCCESS;
    }
}
