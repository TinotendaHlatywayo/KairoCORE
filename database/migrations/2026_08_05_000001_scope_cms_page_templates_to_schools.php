<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_page_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('cms_page_templates', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('cms_page_templates', 'is_school_template')) {
                $table->boolean('is_school_template')->default(false)->after('is_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_page_templates', function (Blueprint $table): void {
            $table->dropColumn(['school_id', 'is_school_template']);
        });
    }
};
