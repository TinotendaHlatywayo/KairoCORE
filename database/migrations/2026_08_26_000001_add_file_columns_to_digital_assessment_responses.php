<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_assessment_responses', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('learner_answer');
            $table->string('original_filename')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('original_filename');
            $table->string('file_mime')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('digital_assessment_responses', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_filename', 'file_size', 'file_mime']);
        });
    }
};
