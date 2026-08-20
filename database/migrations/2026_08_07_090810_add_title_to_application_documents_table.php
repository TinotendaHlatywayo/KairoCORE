<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('application_documents')) {
            return;
        }

        if (Schema::hasColumn('application_documents', 'title')) {
            return;
        }

        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('title')->nullable()->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
