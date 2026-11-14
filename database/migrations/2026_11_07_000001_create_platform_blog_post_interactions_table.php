<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_blog_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('platform_blog_posts')->cascadeOnDelete();
            $table->string('browser_token', 64)->index();
            $table->timestamps();

            $table->unique(['blog_post_id', 'browser_token']);
        });

        Schema::create('platform_blog_post_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('platform_blog_posts')->cascadeOnDelete();
            $table->string('target', 40)->default('native');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_blog_post_shares');
        Schema::dropIfExists('platform_blog_post_likes');
    }
};
