<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop standard unique email constraint safely
            $table->dropUnique(['email']);

            // Add nullable school link (null represents Platform Super Admin)
            $table->foreignId('school_id')
                ->after('id')
                ->nullable()
                ->constrained('schools')
                ->onDelete('cascade');

            // Set composite unique index
            $table->unique(['school_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'email']);
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
            $table->unique('email');
        });
    }
};
