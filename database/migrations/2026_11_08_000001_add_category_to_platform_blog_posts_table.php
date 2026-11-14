<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_blog_posts', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('excerpt');
        });

        DB::table('platform_blog_posts')->whereNull('category')->update(['category' => 'Insights']);
        DB::table('platform_blog_posts')->where('title', 'like', '%fee%')->update(['category' => 'Fees']);
        DB::table('platform_blog_posts')->where('title', 'like', '%admission%')->update(['category' => 'Academics']);
        DB::table('platform_blog_posts')->where('title', 'like', '%security%')->update(['category' => 'Security']);
    }

    public function down(): void
    {
        Schema::table('platform_blog_posts', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
