<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the "users" recipient scope so the platform can address specific users
 * of a tenant (role- and name-filtered) instead of only whole schools.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_messages', function (Blueprint $table) {
            $table->enum('recipient_scope', ['all', 'selected', 'single', 'users'])
                ->default('single')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('platform_messages', function (Blueprint $table) {
            $table->enum('recipient_scope', ['all', 'selected', 'single'])
                ->default('single')
                ->change();
        });
    }
};
