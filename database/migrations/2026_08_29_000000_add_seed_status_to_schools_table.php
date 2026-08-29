<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Demo-data lifecycle: pending -> seeding -> seeded | failed.
            $table->string('seed_status')->nullable()->after('has_dummy_data');
            $table->timestamp('seeded_at')->nullable()->after('seed_status');
            $table->text('seed_error')->nullable()->after('seeded_at');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['seed_status', 'seeded_at', 'seed_error']);
        });
    }
};
