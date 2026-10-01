<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Security\RoleCatalogue;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Admin\Services\SystemRolePresets;

/**
 * Bring the default roles of one or all schools back in step with the catalogue.
 *
 * Roles are normally kept current on their own: a new school is provisioned with
 * them, approval refreshes the role it hands out, and `SystemRolePresets` is
 * called from the flows that assign defaults. This command exists for the
 * schools that predate that — a role row written before a catalogue release
 * stays as it was until something explicitly revisits it.
 *
 * Roles an administrator has tailored are reported and left alone, because a
 * deliberate narrowing must survive a defaults refresh. `--restore-defaults` is
 * the deliberate override for those.
 */
class SyncCatalogueRoles extends Command
{
    protected $signature = 'schoolcore:sync-catalogue-roles
        {school? : Optional school ID. Defaults to the current tenant, or every school with --all.}
        {--all : Sync every school on the platform.}
        {--restore-defaults : Also reset roles an administrator has tailored back to the catalogue defaults.}';

    protected $description = 'Create missing default roles and refresh untouched ones for one or all schools, so every role grants exactly what the role catalogue defines.';

    public function handle(): int
    {
        $schools = $this->resolveSchools();

        if ($schools->isEmpty()) {
            $this->error('No schools found for the given school ID.');

            return self::FAILURE;
        }

        foreach ($schools as $school) {
            if ($this->option('restore-defaults')) {
                SystemRolePresets::restoreDefaults($school->id);
            }

            $result = SystemRolePresets::provisionForSchool($school);

            $this->info(sprintf(
                'School #%d (%s): %d created, %d refreshed, %d tailored by an administrator.',
                $school->id,
                $school->name,
                count($result['created']),
                count($result['refreshed']),
                count($result['customised']),
            ));

            foreach ($result['created'] as $key) {
                $this->line('  + '.RoleCatalogue::label($key).' created with '.count(RoleCatalogue::permissionsFor($key)).' permissions');
            }

            foreach ($result['refreshed'] as $key) {
                $this->line('  ~ '.RoleCatalogue::label($key).' refreshed to catalogue defaults');
            }

            foreach ($result['customised'] as $key) {
                $this->line('  = '.RoleCatalogue::label($key).' left as the administrator tailored it (use --restore-defaults to reset)');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, School>
     */
    protected function resolveSchools()
    {
        $query = School::query()->orderBy('id');

        if ($school = $this->argument('school')) {
            $query->where('id', (int) $school);
        }

        return $query->get();
    }
}
