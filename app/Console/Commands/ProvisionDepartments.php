<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Modules\Admin\Services\DepartmentPermissionPresets;

class ProvisionDepartments extends Command
{
    protected $signature = 'schoolcore:provision-departments {school? : Optional school ID. Defaults to provisioning every active school.}';

    protected $description = 'Create the built-in departments (Finance, Clinic, Inventory & Assets, ...) with their default permission bundles for one or all schools.';

    public function handle(): int
    {
        $schoolId = $this->argument('school');

        $query = School::query();
        if ($schoolId) {
            $query->where('id', (int) $schoolId);
        }

        $schools = $query->get();

        if ($schools->isEmpty()) {
            $this->error('No schools found.');

            return self::FAILURE;
        }

        foreach ($schools as $school) {
            $created = DepartmentPermissionPresets::provisionForSchool($school);
            $this->info(sprintf(
                'School #%d (%s): %d department(s) created.',
                $school->id,
                $school->name,
                count($created),
            ));
        }

        return self::SUCCESS;
    }
}
