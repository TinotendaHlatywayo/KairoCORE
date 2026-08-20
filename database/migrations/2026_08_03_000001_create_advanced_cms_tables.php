<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade cms_websites table if exists, or create if not
        if (! Schema::hasTable('cms_websites')) {
            Schema::create('cms_websites', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->unique();
                $table->string('active_template')->default('modern_international');
                $table->timestamps();
            });
        }

        Schema::table('cms_websites', function (Blueprint $table) {
            $cols = [
                'font_primary' => fn () => $table->string('font_primary')->default('Inter'),
                'font_secondary' => fn () => $table->string('font_secondary')->default('Outfit'),
                'color_primary' => fn () => $table->string('color_primary')->default('#1e40af'),
                'color_secondary' => fn () => $table->string('color_secondary')->default('#0ea5e9'),
                'color_accent' => fn () => $table->string('color_accent')->default('#f59e0b'),
                'color_background' => fn () => $table->string('color_background')->default('#ffffff'),
                'color_text' => fn () => $table->string('color_text')->default('#1f2937'),
                'color_card_bg' => fn () => $table->string('color_card_bg')->default('#f9fafb'),
                'color_border' => fn () => $table->string('color_border')->default('#e5e7eb'),
                'color_error' => fn () => $table->string('color_error')->default('#ef4444'),
                'color_success' => fn () => $table->string('color_success')->default('#10b981'),
                'color_warning' => fn () => $table->string('color_warning')->default('#f59e0b'),
                'design_radius' => fn () => $table->string('design_radius')->default('md'),
                'design_shadow' => fn () => $table->string('design_shadow')->default('md'),
                'design_container' => fn () => $table->string('design_container')->default('wide'),
                'design_button_style' => fn () => $table->string('design_button_style')->default('pill'),
                'design_spacing_scale' => fn () => $table->string('design_spacing_scale')->default('md'),
                'navigation_menu' => fn () => $table->json('navigation_menu')->nullable(),
                'footer_menu' => fn () => $table->json('footer_menu')->nullable(),
                'social_links' => fn () => $table->json('social_links')->nullable(),
                'seo_title_suffix' => fn () => $table->string('seo_title_suffix')->nullable(),
                'seo_global_description' => fn () => $table->text('seo_global_description')->nullable(),
                'seo_og_image' => fn () => $table->string('seo_og_image')->nullable(),
                'seo_default_meta' => fn () => $table->json('seo_default_meta')->nullable(),
                'logo_light_path' => fn () => $table->string('logo_light_path')->nullable(),
                'logo_dark_path' => fn () => $table->string('logo_dark_path')->nullable(),
                'favicon_path' => fn () => $table->string('favicon_path')->nullable(),
                'apple_touch_icon_path' => fn () => $table->string('apple_touch_icon_path')->nullable(),
                'custom_css' => fn () => $table->text('custom_css')->nullable(),
                'custom_js' => fn () => $table->text('custom_js')->nullable(),
                'custom_head' => fn () => $table->text('custom_head')->nullable(),
                'announcement_banner' => fn () => $table->json('announcement_banner')->nullable(),
                'theme_overrides' => fn () => $table->json('theme_overrides')->nullable(),
                'font_load_strategy' => fn () => $table->json('font_load_strategy')->nullable(),
                'enable_animations' => fn () => $table->boolean('enable_animations')->default(true),
                'enable_lazy_load' => fn () => $table->boolean('enable_lazy_load')->default(true),
            ];

            foreach ($cols as $colName => $addCol) {
                if (! Schema::hasColumn('cms_websites', $colName)) {
                    $addCol();
                }
            }
        });

        // 2. Upgrade cms_pages table
        if (! Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('cms_website_id');
                $table->string('title');
                $table->string('slug');
                $table->timestamps();
            });
        }

        Schema::table('cms_pages', function (Blueprint $table) {
            $cols = [
                'parent_slug' => fn () => $table->string('parent_slug')->nullable(),
                'sort_order' => fn () => $table->unsignedInteger('sort_order')->default(0),
                'depth' => fn () => $table->unsignedInteger('depth')->default(0),
                'is_published' => fn () => $table->boolean('is_published')->default(false),
                'is_homepage' => fn () => $table->boolean('is_homepage')->default(false),
                'is_protected' => fn () => $table->boolean('is_protected')->default(false),
                'password' => fn () => $table->string('password')->nullable(),
                'hide_from_nav' => fn () => $table->boolean('hide_from_nav')->default(false),
                'hide_from_sitemap' => fn () => $table->boolean('hide_from_sitemap')->default(false),
                'page_template' => fn () => $table->string('page_template')->default('default'),
                'page_layout' => fn () => $table->json('page_layout')->nullable(),
                'blocks' => fn () => $table->longText('blocks')->nullable(),
                'draft_blocks' => fn () => $table->longText('draft_blocks')->nullable(),
                'seo_title' => fn () => $table->string('seo_title')->nullable(),
                'seo_description' => fn () => $table->text('seo_description')->nullable(),
                'seo_keywords' => fn () => $table->string('seo_keywords')->nullable(),
                'seo_og_title' => fn () => $table->string('seo_og_title')->nullable(),
                'seo_og_description' => fn () => $table->text('seo_og_description')->nullable(),
                'seo_og_image' => fn () => $table->string('seo_og_image')->nullable(),
                'seo_twitter_card' => fn () => $table->string('seo_twitter_card')->nullable(),
                'seo_structured_data' => fn () => $table->json('seo_structured_data')->nullable(),
                'canonical_url' => fn () => $table->string('canonical_url')->nullable(),
                'noindex' => fn () => $table->boolean('noindex')->default(false),
                'nofollow' => fn () => $table->boolean('nofollow')->default(false),
                'og_type' => fn () => $table->string('og_type')->default('website'),
                'og_locale' => fn () => $table->string('og_locale')->default('en_US'),
                'version' => fn () => $table->unsignedInteger('version')->default(1),
                'published_at' => fn () => $table->timestamp('published_at')->nullable(),
                'published_by' => fn () => $table->unsignedBigInteger('published_by')->nullable(),
                'custom_css' => fn () => $table->text('custom_css')->nullable(),
                'custom_js' => fn () => $table->text('custom_js')->nullable(),
                'page_settings' => fn () => $table->json('page_settings')->nullable(),
                'scripts' => fn () => $table->json('scripts')->nullable(),
            ];

            foreach ($cols as $colName => $addCol) {
                if (! Schema::hasColumn('cms_pages', $colName)) {
                    $addCol();
                }
            }
        });

        // 3. Reusable Global Blocks
        if (! Schema::hasTable('cms_reusable_blocks')) {
            Schema::create('cms_reusable_blocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('name');
                $table->string('category')->default('custom');
                $table->string('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->longText('content')->nullable();
                $table->json('block_types')->nullable();
                $table->boolean('is_global')->default(false);
                $table->unsignedInteger('usage_count')->default(0);
                $table->timestamps();
            });
        }

        // 4. Media Library
        if (! Schema::hasTable('cms_media')) {
            Schema::create('cms_media', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('uuid')->unique();
                $table->string('filename');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->string('extension');
                $table->unsignedBigInteger('file_size');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('url');
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('dominant_color')->nullable();
                $table->json('exif')->nullable();
                $table->string('folder')->default('uploads');
                $table->json('tags')->nullable();
                $table->string('alt_text')->nullable();
                $table->string('caption')->nullable();
                $table->string('credit')->nullable();
                $table->unsignedInteger('usage_count')->default(0);
                $table->json('used_in')->nullable();
                $table->json('variants')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 5. Navigation Menus
        if (! Schema::hasTable('cms_navigation_menus')) {
            Schema::create('cms_navigation_menus', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('cms_website_id');
                $table->string('name');
                $table->string('handle')->unique();
                $table->string('location')->default('header');
                $table->json('items')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 6. Page Versions
        if (! Schema::hasTable('cms_page_versions')) {
            Schema::create('cms_page_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('cms_page_id');
                $table->unsignedBigInteger('cms_website_id');
                $table->unsignedInteger('version_number');
                $table->string('title');
                $table->string('slug');
                $table->longText('blocks');
                $table->json('page_settings')->nullable();
                $table->json('seo_data')->nullable();
                $table->string('change_summary')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('created_by_type')->nullable();
                $table->boolean('is_autosave')->default(false);
                $table->timestamps();
            });
        }

        // 7. Global Components
        if (! Schema::hasTable('cms_global_components')) {
            Schema::create('cms_global_components', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('cms_website_id');
                $table->string('name');
                $table->string('type');
                $table->string('handle')->unique();
                $table->longText('content')->nullable();
                $table->json('settings')->nullable();
                $table->json('conditions')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 8. Form Submissions
        if (! Schema::hasTable('cms_form_submissions')) {
            Schema::create('cms_form_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('cms_page_id')->nullable();
                $table->string('form_handle');
                $table->json('form_data');
                $table->json('meta')->nullable();
                $table->string('status')->default('new');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->timestamp('replied_at')->nullable();
                $table->timestamps();
            });
        }

        // 9. Dynamic Sources
        if (! Schema::hasTable('cms_dynamic_sources')) {
            Schema::create('cms_dynamic_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('handle')->unique();
                $table->string('name');
                $table->string('module');
                $table->string('model_class');
                $table->json('query_config')->nullable();
                $table->json('field_mapping')->nullable();
                $table->json('template_config')->nullable();
                $table->unsignedInteger('cache_ttl')->default(300);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Page Templates
        if (! Schema::hasTable('cms_page_templates')) {
            Schema::create('cms_page_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('handle')->unique();
                $table->string('description')->nullable();
                $table->string('category')->default('general');
                $table->string('thumbnail')->nullable();
                $table->longText('blocks');
                $table->json('page_settings')->nullable();
                $table->json('seo_defaults')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 11. User Preferences
        if (! Schema::hasTable('cms_user_preferences')) {
            Schema::create('cms_user_preferences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('user_id');
                $table->json('editor_settings')->nullable();
                $table->json('preview_settings')->nullable();
                $table->json('block_favorites')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_user_preferences');
        Schema::dropIfExists('cms_page_templates');
        Schema::dropIfExists('cms_dynamic_sources');
        Schema::dropIfExists('cms_form_submissions');
        Schema::dropIfExists('cms_global_components');
        Schema::dropIfExists('cms_page_versions');
        Schema::dropIfExists('cms_navigation_menus');
        Schema::dropIfExists('cms_media');
    }
};
