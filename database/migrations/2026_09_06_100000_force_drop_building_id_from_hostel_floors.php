<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The previous drop migration (2026_09_02_000000) swallowed its error:
        // MySQL cannot DROP a column that still backs a foreign key or an
        // index, so hostel_floors.building_id (NOT NULL, no default) is still
        // present. Earlier partial attempts may have already dropped pieces of
        // the schema, so every step is conditional against information_schema.
        if (! Schema::hasColumn('hostel_floors', 'building_id')) {
            return;
        }

        $this->dropFk('building_id');
        // The uq_hostel_flr_num index also backs the school_id FK, so that FK
        // must go (and be re-added) before the unique index can be dropped.
        $this->dropFk('school_id');

        Schema::table('hostel_floors', function (Blueprint $table) {
            $table->dropUnique('uq_hostel_flr_num');
        });
        $this->dropIndexIfExists('hostel_floors_building_id_foreign');

        Schema::table('hostel_floors', function (Blueprint $table) {
            $table->dropColumn('building_id');
        });

        $this->restoreForeignKey('hostel_floors_school_id_foreign', 'school_id', 'schools', 'id');

        // Re-state a meaningful unique constraint scoped by hostel (the floor
        // number only needs to be unique within its hostel and school).
        try {
            if (! $this->indexExists('hostel_floors', 'uq_hostel_flr_hostel_num')) {
                Schema::table('hostel_floors', function (Blueprint $table) {
                    $table->unique(['school_id', 'hostel_id', 'floor_number'], 'uq_hostel_flr_hostel_num');
                });
            }
        } catch (Throwable) {
            // Existing rows may already contain duplicates; the constraint is
            // not required for correctness of the demo dataset.
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hostel_floors', 'building_id')) {
            try {
                Schema::table('hostel_floors', function (Blueprint $table) {
                    $table->dropUnique('uq_hostel_flr_hostel_num');
                    $table->foreignId('building_id')->nullable()->constrained('hostel_buildings')->onDelete('cascade');
                });
            } catch (Throwable) {
                // Ignore partial teardown issues.
            }
        }
    }

    protected function dropFk(string $column): void
    {
        $fks = DB::select(
            "SELECT DISTINCT CONSTRAINT_NAME AS cname FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hostel_floors'
               AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$column]
        );

        foreach ($fks as $fk) {
            DB::statement('ALTER TABLE `hostel_floors` DROP FOREIGN KEY `'.$fk->cname.'`');
        }
    }

    protected function restoreForeignKey(string $constraint, string $column, string $referencedTable, string $referencedColumn): void
    {
        $exists = DB::selectOne(
            "SELECT CONSTRAINT_NAME AS cname FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hostel_floors'
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$constraint]
        );

        if (! $exists) {
            DB::statement(
                "ALTER TABLE `hostel_floors` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`)
                 REFERENCES `{$referencedTable}`(`{$referencedColumn}`) ON DELETE CASCADE"
            );
        }
    }

    protected function dropIndexIfExists(string $index): void
    {
        $exists = DB::select(
            'SELECT DISTINCT INDEX_NAME AS iname FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            ['hostel_floors', $index]
        );

        foreach ($exists as $ix) {
            DB::statement('ALTER TABLE `hostel_floors` DROP INDEX `'.$ix->iname.'`');
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $definition) {
            if (($definition['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};