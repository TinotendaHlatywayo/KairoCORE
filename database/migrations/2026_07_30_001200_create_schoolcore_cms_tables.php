<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Core Website Container
        Schema::create('cms_websites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('active_template')->default('modern_international');

            // Global Branding & Visual Token Palette
            $table->string('font_primary')->default('Inter');
            $table->string('font_secondary')->default('Outfit');
            $table->string('color_primary')->default('#1e40af'); // Tailwind blue-800
            $table->string('color_secondary')->default('#0ea5e9'); // Tailwind sky-500
            $table->string('color_accent')->default('#f59e0b'); // Tailwind amber-500
            $table->string('color_background')->default('#ffffff');
            $table->string('color_text')->default('#1f2937'); // Tailwind gray-800
            $table->string('color_card_bg')->default('#f9fafb'); // Tailwind gray-50

            // Site Options
            $table->json('navigation_menu')->nullable(); // Drag-and-drop link list
            $table->json('footer_menu')->nullable();
            $table->json('social_links')->nullable();
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();

            // Assets
            $table->string('logo_light_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();

            // SEO & Global Metas
            $table->string('seo_title_suffix')->nullable();
            $table->text('seo_global_description')->nullable();
            $table->string('seo_og_image')->nullable();
            $table->timestamps();

            $table->unique('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });

        // 2. Visual Content Pages
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('cms_website_id');
            $table->string('title');
            $table->string('slug');
            $table->boolean('is_published')->default(true);
            $table->boolean('is_homepage')->default(false);

            // Core Page Layout Blocks (JSON Canvas Payload)
            $table->longText('blocks')->nullable(); // Structured JSON of visual section configurations

            // Individual SEO Parameters
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'slug']);
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('cms_website_id')->references('id')->on('cms_websites')->onDelete('cascade');
        });

        // 3. Reusable Global Blocks / Saved Layouts
        Schema::create('cms_reusable_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('category')->default('custom');
            $table->longText('content')->nullable(); // Saved block settings JSON
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_reusable_blocks');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('cms_websites');
    }
};
