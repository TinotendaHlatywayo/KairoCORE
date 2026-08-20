<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 20)->default('active')->after('custom_role_id');
            }

            if (! Schema::hasColumn('users', 'requested_role')) {
                // Registered-but-pending applicant's claimed role category:
                // administrator | student | teaching_staff | non_teaching_staff
                $table->string('requested_role', 50)->nullable()->after('account_status');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 60)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('requested_role');
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('users', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approved_by')) {
                $table->dropForeign(['approved_by']);
            }

            foreach ([
                'rejected_reason',
                'approved_at',
                'approved_by',
                'phone',
                'requested_role',
                'account_status',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
