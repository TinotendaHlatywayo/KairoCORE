<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('uploaded_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->enum('type', ['academic_resource', 'publication', 'research_project', 'institutional_archive']);
            $table->string('subtype');
            $table->text('abstract_description');
            $table->enum('visibility', ['private', 'library_only', 'teachers_only', 'students_only', 'public'])->default('library_only');
            $table->enum('workflow_status', ['draft', 'submitted', 'under_review', 'approved', 'archived'])->default('draft');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'type', 'workflow_status'], 'idx_knowledge_assets_lookup');
        });

        Schema::create('knowledge_asset_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('knowledge_asset_id')->constrained('knowledge_assets')->onDelete('cascade');
            $table->string('version_number');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->text('change_log')->nullable();
            $table->foreignId('uploaded_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('knowledge_asset_versions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_assets', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('knowledge_asset_versions');
        Schema::dropIfExists('knowledge_assets');
    }
};
