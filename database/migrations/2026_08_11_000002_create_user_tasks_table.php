<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_tasks')) {
            Schema::create('user_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('assigned_to_id')->nullable();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->string('due_time', 10)->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('assigned_to_id')->references('id')->on('users')->nullOnDelete();

                $table->index(['school_id', 'assigned_to_id', 'status']);
                $table->index(['school_id', 'created_by_id', 'status']);
            });
        }

        if (! Schema::hasColumn('users', 'do_not_disturb')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('do_not_disturb')->default(false)->after('rejected_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'do_not_disturb')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('do_not_disturb');
            });
        }

        Schema::dropIfExists('user_tasks');
    }
};
