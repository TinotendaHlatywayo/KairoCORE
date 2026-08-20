<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'activation_token')) {
                $table->string('activation_token', 100)->nullable()->unique()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'activation_token_expires_at')) {
                $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['activation_token', 'activation_token_expires_at']);
        });
    }
};
