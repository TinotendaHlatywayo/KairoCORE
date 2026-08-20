<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site templates (school1, school2...) are named bundles that wrap a
        // shadow website holding the template's theme, structure and per-page
        // design. A school may keep many templates but only one is active on
        // the public live site.
        if (! Schema::hasTable('cms_site_templates')) {
            Schema::create('cms_site_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->unsignedBigInteger('cms_website_id')->nullable();
                $table->timestamps();
            });
        }

        // Allow multiple websites per school: the public live site plus one
        // shadow website per saved template. The unique index is what backs the
        // school_id foreign key, so drop both and rebuild a plain FK index.
        Schema::table('cms_websites', function (Blueprint $table) {
            if (Schema::hasIndex('cms_websites', 'cms_websites_school_id_unique')) {
                $table->dropForeign('cms_websites_school_id_foreign');
                $table->dropUnique('cms_websites_school_id_unique');
                $table->index('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            }
            if (! Schema::hasColumn('cms_websites', 'is_template_site')) {
                $table->boolean('is_template_site')->default(false)->after('school_id');
            }
            if (! Schema::hasColumn('cms_websites', 'active_site_template_id')) {
                $table->unsignedBigInteger('active_site_template_id')->nullable()->after('is_template_site');
            }
        });

        // Pages are unique per website (live + each template), not per school.
        // The school_id FK is backed by the unique composite index, so rebuild
        // the FK on a plain index instead.
        if (Schema::hasIndex('cms_pages', 'cms_pages_school_id_slug_unique')) {
            Schema::table('cms_pages', function (Blueprint $table) {
                $table->dropForeign('cms_pages_school_id_foreign');
                $table->dropUnique('cms_pages_school_id_slug_unique');
                $table->index('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            if (! Schema::hasIndex('cms_pages', 'cms_pages_school_id_slug_unique')) {
                $table->unique(['school_id', 'slug']);
            }
        });

        Schema::table('cms_websites', function (Blueprint $table) {
            $table->dropColumn(['is_template_site', 'active_site_template_id']);
            if (! Schema::hasIndex('cms_websites', 'cms_websites_school_id_unique')) {
                $table->unique('school_id');
            }
        });
        Schema::dropIfExists('cms_site_templates');
    }
};
