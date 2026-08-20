<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');

            // Drop old unique constraint and establish a tenant-aware unique constraint
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['school_id', 'name', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name', 'guard_name']);
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
            $table->unique(['name', 'guard_name']);
        });
    }
};
