<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // courses table - teacher_id already exists, workflow_status and workflow_completed_at already exist
        if (! Schema::hasColumn('courses', 'teacher_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('code');
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // subjects table - workflow_completed_at exists, need is_elective and workflow_status
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'is_elective')) {
                $table->boolean('is_elective')->default(false)->after('credit_weight');
            }
            if (! Schema::hasColumn('subjects', 'workflow_status')) {
                $table->string('workflow_status', 50)->default('pending')->after('is_elective');
            }
        });

        // classrooms table - need location, description, features
        Schema::table('classrooms', function (Blueprint $table) {
            if (! Schema::hasColumn('classrooms', 'location')) {
                $table->string('location')->nullable()->after('capacity');
            }
            if (! Schema::hasColumn('classrooms', 'description')) {
                $table->text('description')->nullable()->after('location');
            }
            if (! Schema::hasColumn('classrooms', 'features')) {
                $table->json('features')->nullable()->after('description');
            }
        });

        // sections table - workflow_completed_at exists, need workflow_status
        if (! Schema::hasColumn('sections', 'workflow_status')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->string('workflow_status', 50)->default('pending')->after('capacity');
            });
        }

        // academic_years table - workflow_completed_at, workflow_status, workflow_metadata already exist
        // Nothing to add

        // terms table - workflow_completed_at already exists
        // Nothing to add
    }

    public function down(): void
    {
        // Only drop columns we actually added in this migration
        if (Schema::hasColumn('courses', 'teacher_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropForeign(['teacher_id']);
                $table->dropColumn(['teacher_id']);
            });
        }

        Schema::table('subjects', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('subjects', 'is_elective')) {
                $columnsToDrop[] = 'is_elective';
            }
            if (Schema::hasColumn('subjects', 'workflow_status')) {
                $columnsToDrop[] = 'workflow_status';
            }
            if ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('classrooms', 'location')) {
                $columnsToDrop[] = 'location';
            }
            if (Schema::hasColumn('classrooms', 'description')) {
                $columnsToDrop[] = 'description';
            }
            if (Schema::hasColumn('classrooms', 'features')) {
                $columnsToDrop[] = 'features';
            }
            if ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (Schema::hasColumn('sections', 'workflow_status')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->dropColumn('workflow_status');
            });
        }

        // academic_years columns already existed before this migration - don't drop
        // terms columns already existed before this migration - don't drop
    }
};
