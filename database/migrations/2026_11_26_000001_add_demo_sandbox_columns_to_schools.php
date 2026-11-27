<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->boolean('is_demo_sandbox')->default(false)->after('has_dummy_data');
            $table->boolean('allow_demo_seed_wipe')->default(false)->after('is_demo_sandbox');
            $table->string('demo_session_id', 64)->nullable()->unique()->after('allow_demo_seed_wipe');
            $table->timestamp('expires_at')->nullable()->after('trial_ends_at');
            $table->timestamp('demo_last_activity_at')->nullable()->after('expires_at');
            $table->timestamp('hard_expires_at')->nullable()->after('demo_last_activity_at');
            $table->string('pool_status', 16)->nullable()->after('hard_expires_at');

            $table->index(['is_demo_sandbox', 'expires_at'], 'schools_demo_expiry_index');
            $table->index(['pool_status', 'expires_at'], 'schools_demo_pool_index');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropIndex('schools_demo_expiry_index');
            $table->dropIndex('schools_demo_pool_index');
            $table->dropUnique(['demo_session_id']);
            $table->dropColumn([
                'is_demo_sandbox',
                'allow_demo_seed_wipe',
                'demo_session_id',
                'expires_at',
                'demo_last_activity_at',
                'hard_expires_at',
                'pool_status',
            ]);
        });
    }
};
