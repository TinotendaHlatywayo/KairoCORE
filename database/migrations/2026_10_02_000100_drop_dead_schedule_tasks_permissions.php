<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the `communication.schedule_tasks.*` permission keys.
 *
 * "Schedule & Tasks" was a category hub rather than a page, so the catalogue
 * registered a set of permission keys against it. The hub did not work: it
 * looked for pages grouped inside the communication module, but Schedule lives
 * in the universal module, so the lookup found nothing and every user was left
 * on a spinner reading "Opening category…". The navigation entry now points
 * straight at the Schedule page, and those keys are gone from the catalogue.
 *
 * The keys still sitting in `custom_roles.permissions` and `users.permissions`
 * grant nothing at all — no page maps to them — so they cannot be reaching any
 * screen. They are removed only so that the permission editor does not keep
 * offering checkboxes for a page that is not there.
 *
 * Only these exact prefixes are touched. A role narrowed by hand keeps every
 * other key it was given, including its `permissions_customised` flag, because
 * nobody chose these keys to be removed — they are left over from a catalogue
 * entry that no longer exists.
 */
return new class extends Migration
{
    private const PREFIX = 'communication.schedule_tasks.';

    public function up(): void
    {
        $roles = $this->strip('custom_roles');
        $users = $this->strip('users');

        if ($roles > 0 || $users > 0) {
            Log::info('Dropped dead Schedule & Tasks permissions.', [
                'roles_updated' => $roles,
                'users_updated' => $users,
            ]);
        }
    }

    public function down(): void
    {
        // Deliberately not restored. Putting the keys back would re-offer
        // permission checkboxes for a page that no longer exists, which is the
        // state this migration exists to remove.
    }

    /**
     * @return int the number of rows that actually changed
     */
    private function strip(string $table): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
            return 0;
        }

        $updated = 0;

        DB::table($table)
            ->orderBy('id')
            ->each(function ($row) use ($table, &$updated): void {
                $permissions = json_decode((string) $row->permissions, true);

                if (! is_array($permissions)) {
                    return;
                }

                $kept = array_values(array_filter(
                    $permissions,
                    fn (mixed $key): bool => ! is_string($key) || ! str_starts_with($key, self::PREFIX),
                ));

                if ($kept === array_values($permissions)) {
                    return;
                }

                DB::table($table)
                    ->where('id', $row->id)
                    ->update(['permissions' => json_encode($kept)]);

                $updated++;
            });

        return $updated;
    }
};
