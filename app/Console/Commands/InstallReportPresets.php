<?php

namespace App\Console\Commands;

use App\Models\School;
use Database\Seeders\ReportScheduleSeeder;
use Illuminate\Console\Command;

class InstallReportPresets extends Command
{
    protected $signature = 'schoolcore:install-report-presets
                            {school? : Optional school ID. Defaults to every school.}';

    protected $description = 'Install the shipped report presets into a tenant\'s template library, refreshing any preset that drifted from the catalogue.';

    public function handle(): int
    {
        $schoolId = $this->argument('school');

        $query = School::query();
        if ($schoolId) {
            $query->where('id', (int) $schoolId);
        }

        $schools = $query->orderBy('id')->get();

        if ($schools->isEmpty()) {
            $this->error('No schools found.');

            return self::FAILURE;
        }

        $seeder = app(ReportScheduleSeeder::class);

        foreach ($schools as $school) {
            $seeder->run($school->id);

            $this->info(sprintf(
                'School #%d (%s): report presets installed.',
                $school->id,
                $school->name,
            ));
        }

        return self::SUCCESS;
    }
}
