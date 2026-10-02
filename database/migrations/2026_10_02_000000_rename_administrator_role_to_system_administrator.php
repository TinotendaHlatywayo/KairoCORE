<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the catalogue administrator role to "System Administrator".
 *
 * "Administrator" was ambiguous in a school that has both an administrator and
 * a school administrator: two roles, one distinguishable only by dropping an
 * adjective. The catalogue label was corrected; this brings the stored rows
 * with it, because every screen an administrator reads the role from — the
 * account list, the role editor, the permission summary — reads
 * `custom_roles.name`, not the catalogue.
 *
 * Only rows whose `role_key` is already `administrator` are touched. The table
 * also holds roles an administrator made by hand that happen to be called
 * "Administrator"; those describe somebody else's job and are left exactly as
 * they are.
 *
 * `name` is unique per school, so a school where a *different* role already
 * occupies "System Administrator" cannot simply be overwritten. Those rows are
 * skipped and reported rather than clobbered, leaving the name to be sorted out
 * by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_roles') || ! Schema::hasColumn('custom_roles', 'role_key')) {
            return;
        }

        $renamed = 0;
        $skipped = 0;

        DB::table('custom_roles')
            ->where('role_key', 'administrator')
            ->where('name', 'Administrator')
            ->orderBy('id')
            ->each(function ($role) use (&$renamed, &$skipped): void {
                $taken = DB::table('custom_roles')
                    ->where('school_id', $role->school_id)
                    ->where('name', 'System Administrator')
                    ->where('id', '!=', $role->id)
                    ->exists();

                if ($taken) {
                    // Somebody in this school already holds that name. Renaming
                    // would either violate the unique index or silently take
                    // their role's name, so leave it for a human to resolve.
                    $skipped++;

                    return;
                }

                DB::table('custom_roles')
                    ->where('id', $role->id)
                    ->update(['name' => 'System Administrator']);

                $renamed++;
            });

        if ($renamed > 0 || $skipped > 0) {
            Log::info('Renamed the administrator role.', [
                'renamed' => $renamed,
                'skipped' => $skipped,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('custom_roles') || ! Schema::hasColumn('custom_roles', 'role_key')) {
            return;
        }

        // Only rows this migration renamed go back; a role already called
        // "System Administrator" for some other reason stays that way.
        DB::table('custom_roles')
            ->where('role_key', 'administrator')
            ->where('name', 'System Administrator')
            ->orderBy('id')
            ->each(function ($role): void {
                $taken = DB::table('custom_roles')
                    ->where('school_id', $role->school_id)
                    ->where('name', 'Administrator')
                    ->where('id', '!=', $role->id)
                    ->exists();

                if ($taken) {
                    return;
                }

                DB::table('custom_roles')
                    ->where('id', $role->id)
                    ->update(['name' => 'Administrator']);
            });
    }
};
