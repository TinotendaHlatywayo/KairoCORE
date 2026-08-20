<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('documents_verified')->default(false)->after('status');
            $table->string('parent_relationship')->nullable()->after('parent_phone');
            $table->date('interview_date')->nullable()->after('documents_verified');
            $table->text('interview_notes')->nullable()->after('interview_date');
            $table->string('interview_status')->default('pending')->after('interview_notes');
            $table->text('decision_notes')->nullable()->after('interview_status');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'documents_verified',
                'parent_relationship',
                'interview_date',
                'interview_notes',
                'interview_status',
                'decision_notes',
            ]);
        });
    }
};
