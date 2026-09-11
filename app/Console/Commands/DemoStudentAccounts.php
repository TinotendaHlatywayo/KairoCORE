<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\DummyDataSeeder;
use Illuminate\Console\Command;

class DemoStudentAccounts extends Command
{
    protected $signature = 'schoolcore:demo-student-accounts
        {school? : School tenant ID. Omitted to prompt or to target all schools with --all}
        {--all : Create demo student accounts for every school that has demo students}
        {--school= : Alias for the positional school argument}';

    protected $description = 'Create student-portal login accounts for seeded demo students';

    public function handle(): int
    {
        $seeder = app(DummyDataSeeder::class);

        $schoolId = (int) ($this->argument('school') ?? $this->option('school') ?? 0);
        $all = (bool) $this->option('all');

        if (! $all && $schoolId <= 0) {
            $schoolId = (int) $this->ask('Enter the target School Tenant ID (school_id)');
        }

        $schoolIds = $all
            ? School::query()->pluck('id')->all()
            : [$schoolId];

        $total = 0;

        foreach ($schoolIds as $id) {
            $school = School::find($id);
            if (! $school) {
                $this->error("School {$id} not found.");

                continue;
            }

            $accounts = $seeder->ensureDemoStudentAccounts($id);

            if (empty($accounts)) {
                $this->info("{$school->name}: no unpaired demo students.");

                continue;
            }

            $total += count($accounts);
            $this->info("{$school->name}: created ".count($accounts).' student portal account(s).');
            $this->newLine();
            $this->table(['Student', 'Username', 'Password'], array_map(
                fn (array $a) => [$a['name'], $a['username'], $a['password']],
                array_slice($accounts, 0, 5)
            ));
            if (count($accounts) > 5) {
                $this->warn('... and '.count($accounts).' more (all use the same password).');
            }
            $this->newLine();
        }

        $this->info('Demo student sign-in: <username> / '.$seeder->demoStudentPassword);

        return Command::SUCCESS;
    }
}