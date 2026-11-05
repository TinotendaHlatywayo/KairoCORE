<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportChiwariraStudents extends Command
{
    protected $signature = 'students:import-chiwarira
        {school? : School tenant ID. Defaults to the first school in the system}';

    protected $description = 'Shortcut for importing the Chiwarira spreadsheets. Use students:import for any other tenant.';

    public function handle(): int
    {
        $folder = '/home/wayne/Downloads/Chiwarira';

        if (! is_dir($folder)) {
            $this->error("Directory not found: {$folder}");

            return Command::FAILURE;
        }

        $arguments = ['folder' => $folder];

        if ($this->argument('school')) {
            $arguments['--school'] = $this->argument('school');
        }

        return $this->call('students:import', $arguments);
    }
}
