<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\StudentCsvService;
use Illuminate\Console\Command;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;

class ImportChiwariraStudents extends Command
{
    protected $signature = 'students:import-chiwarira
        {school? : School tenant ID. Defaults to the first school in the system}';

    protected $description = 'Study and import all student spreadsheets from /home/wayne/Downloads/Chiwarira, handling data entry mistakes and prompting for new class streams.';

    public function handle(): int
    {
        $folder = '/home/wayne/Downloads/Chiwarira';
        if (!is_dir($folder)) {
            $this->error("Directory not found: {$folder}");
            return Command::FAILURE;
        }

        $files = glob("{$folder}/*.csv");
        if (empty($files)) {
            $this->error("No .csv files found in {$folder}");
            return Command::FAILURE;
        }

        $schoolId = (int) ($this->argument('school') ?? School::first()?->id ?? 1);
        $school = School::find($schoolId);
        if (! $school) {
            $this->error("School ID {$schoolId} not found.");
            return Command::FAILURE;
        }

        $this->info("Importing Chiwarira student files for school: {$school->name} (ID: {$schoolId})");
        $this->newLine();

        // Configure missing stream handler to prompt user
        StudentCsvService::$missingStreamHandler = function (int $schoolId, string $courseName, string $sectionName) use ($school) {
            $this->newLine();
            $this->warn("New Class Stream detected: Grade [{$courseName}], Stream [{$sectionName}] is not registered in {$school->name}.");
            
            if ($this->confirm("Do you want to create this class stream now?", true)) {
                // Ensure course exists
                $course = Course::firstOrCreate(
                    ['school_id' => $schoolId, 'name' => $courseName],
                    ['code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $courseName), 0, 10)), 'level' => 1]
                );

                // Ensure section exists
                $section = Section::firstOrCreate(
                    ['school_id' => $schoolId, 'course_id' => $course->id, 'name' => $sectionName],
                    ['code' => strtoupper($sectionName), 'capacity' => 50]
                );

                $this->info("Successfully created Grade [{$courseName}] and Stream [{$sectionName}]. Continuing import...");
                return true;
            }

            return false;
        };

        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            $this->info("Processing: {$filename}");

            try {
                // Read headers to build automatic mapping
                $headers = StudentCsvService::readCsvHeaders($file);
                if (empty($headers)) {
                    $this->warn("  -> Skipped: No readable headers.");
                    continue;
                }

                $map = StudentCsvService::guessMapping($headers);
                $result = StudentCsvService::import($file, $schoolId, $map);

                $totalSuccess += $result['success'];
                $totalFailed += count($result['failures']);

                $this->info("  -> Imported: {$result['success']} students successfully. Failures: " . count($result['failures']));

                if (!empty($result['failures'])) {
                    foreach (array_slice($result['failures'], 0, 3) as $fail) {
                        $this->line("     Row {$fail['row']}: " . implode('; ', $fail['errors']));
                    }
                    if (count($result['failures']) > 3) {
                        $this->line("     ... and " . (count($result['failures']) - 3) . " more failures.");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("  -> Error processing {$filename}: " . $e->getMessage());
            }
            $this->newLine();
        }

        $this->info("=== Chiwarira Import Completed ===");
        $this->info("Total successfully imported: {$totalSuccess}");
        $this->info("Total failed rows: {$totalFailed}");

        return Command::SUCCESS;
    }
}
