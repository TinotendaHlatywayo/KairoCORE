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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('document_contract')->nullable()->after('avatar_path');
            $table->string('document_academic')->nullable()->after('document_contract');
            $table->string('document_professional')->nullable()->after('document_academic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['document_contract', 'document_academic', 'document_professional']);
        });
    }
};
