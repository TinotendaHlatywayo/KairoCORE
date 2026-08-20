<?php

namespace App\Console\Commands;

use App\Services\DummyDataSeeder;
use Illuminate\Console\Command;

class ManageDummyData extends Command
{
    protected $signature = 'schoolcore:dummy {--action= : Options are "seed" or "wipe"}';

    protected $description = 'Seed or wipe dummy student and assessment data safely in Schoolcore';

    public function handle()
    {
        $action = $this->option('action');

        if (! $action || ! in_array($action, ['seed', 'wipe'])) {
            $action = $this->choice('Which action would you like to perform?', ['seed', 'wipe'], 0);
        }

        if ($action === 'seed') {
            $this->seedDummyData();
        } else {
            $this->wipeDummyData();
        }

        return Command::SUCCESS;
    }

    protected function seedDummyData()
    {
        $schoolId = (int) $this->ask('Enter the target School Tenant ID (school_id) to seed');

        if (empty($schoolId)) {
            $this->error('School Tenant ID is required.');

            return;
        }

        $this->info("Initializing Schoolcore playground sandbox for tenant {$schoolId}...");

        $summary = app(DummyDataSeeder::class)->seed($schoolId, function (string $message) {
            $this->info($message);
        });

        $this->info(sprintf(
            'Schoolcore playground seeded successfully. Loaded %d students across %d stream(s) and generated %d academic reports.',
            $summary['students'],
            $summary['sections'],
            $summary['reports']
        ));
    }

    protected function wipeDummyData()
    {
        $schoolId = (int) $this->ask('Enter the target School Tenant ID (school_id) to wipe');

        if (empty($schoolId)) {
            $this->error('School Tenant ID is required.');

            return;
        }

        if (! $this->confirm('This will permanently delete all generated testing data. Are you sure you want to proceed?')) {
            $this->info('Wipe cancelled.');

            return;
        }

        $this->info('Cleaning Schoolcore database records...');

        $deleted = app(DummyDataSeeder::class)->wipe($schoolId);

        if ($deleted === 0) {
            $this->warn('No testing data found to wipe.');

            return;
        }

        $this->info("Wiped successfully ({$deleted} test students removed).");
    }
}
