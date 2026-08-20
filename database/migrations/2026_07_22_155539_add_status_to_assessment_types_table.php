<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_types', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_types', 'status')) {
                // Defaults to 'marking' so your existing marks sheets show up instantly on the workspace!
                $table->string('status')->default('marking');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_types', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
