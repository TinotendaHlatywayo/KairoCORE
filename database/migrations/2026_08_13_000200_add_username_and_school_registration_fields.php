<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
            }
        });

        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'country')) {
                $table->string('country')->nullable()->after('name');
            }
            if (! Schema::hasColumn('schools', 'physical_address')) {
                $table->text('physical_address')->nullable()->after('country');
            }
            if (! Schema::hasColumn('schools', 'language')) {
                $table->string('language')->default('english')->after('physical_address');
            }
            if (! Schema::hasColumn('schools', 'institution_type')) {
                $table->string('institution_type')->default('secondary')->after('language');
            }
            if (! Schema::hasColumn('schools', 'other_institution_type')) {
                $table->string('other_institution_type')->nullable()->after('institution_type');
            }
            if (! Schema::hasColumn('schools', 'phone')) {
                $table->string('phone')->nullable()->after('other_institution_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'physical_address',
                'language',
                'institution_type',
                'other_institution_type',
                'phone',
            ]);
        });
    }
};
