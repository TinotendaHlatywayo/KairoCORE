<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Permanent school deletion must purge every row referencing the school
 * across the whole schema and then hard-delete the school row itself.
 *
 * Production regressed here once: the delete surfaced the generic
 * "Some records could not be removed" notification, so this covers the
 * exact sequence the Filament DeleteAction runs — soft delete, then
 * forceDelete (forceDeleting purge + final row delete) — against the
 * same MySQL information_schema the server uses.
 *
 * Runs against the real dev database (like WebsiteMediaWebpConversionTest);
 * every row it creates carries a unique subdomain/email and is removed
 * by the assertions themselves.
 */
class SchoolForceDeletePurgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', '3306');
        config()->set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        config()->set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    /** Every table in this schema carrying a school_id column. */
    private function schoolTables(): array
    {
        return array_map(
            static fn (object $row): string => (string) $row->table_name,
            DB::select(
                'SELECT TABLE_NAME AS table_name
                   FROM information_schema.COLUMNS
                  WHERE table_schema = DATABASE()
                    AND column_name = ?',
                ['school_id']
            )
        );
    }

    public function test_force_delete_purges_school_rows_across_schema(): void
    {
        $school = School::create([
            'name' => 'Purge Regression School',
            'subdomain' => 'purge-reg-'.uniqid(),
        ]);

        $user = User::factory()->create([
            'school_id' => $school->id,
        ]);

        $school->delete();
        $school->forceDelete();

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        // Users soft-delete via Eloquent; the forceDeleting purge is what
        // hard-removes them — a surviving row would block the school's
        // final DELETE with a foreign-key error on production.
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        foreach ($this->schoolTables() as $table) {
            $remaining = DB::table($table)->where('school_id', $school->id)->count();
            $this->assertSame(0, $remaining, "{$table} still references deleted school {$school->id}");
        }
    }

    public function test_force_delete_succeeds_for_a_school_with_child_rows(): void
    {
        $school = School::create([
            'name' => 'Purge Regression Children',
            'subdomain' => 'purge-children-'.uniqid(),
        ]);

        $staff = User::factory()->count(3)->create([
            'school_id' => $school->id,
        ]);

        $school->delete();

        // The event order is forceDeleting (purge, FK checks off) → deleting
        // (users hook) → hard DELETE of the school row (FK checks back on).
        // If any referrer survived, the last step raises 1451 and the action
        // reports "Could not delete this school".
        $school->forceDelete();

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);

        foreach ($staff as $member) {
            $this->assertDatabaseMissing('users', ['id' => $member->id]);
        }

        foreach ($this->schoolTables() as $table) {
            $remaining = DB::table($table)->where('school_id', $school->id)->count();
            $this->assertSame(0, $remaining, "{$table} still references deleted school {$school->id}");
        }
    }
}
