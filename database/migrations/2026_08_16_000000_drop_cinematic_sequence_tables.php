<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cinematic_scenes');
        Schema::dropIfExists('cinematic_sequences');
    }

    public function down(): void
    {
        Schema::create('cinematic_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('template');
            $table->unsignedInteger('total_frames')->default(0);
            $table->string('source_path')->nullable();
            $table->timestamps();

            $table->index('school_id');
        });

        Schema::create('cinematic_scenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cinematic_sequence_id');
            $table->string('label');
            $table->unsignedInteger('frame');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('cinematic_sequence_id');
        });
    }
};
