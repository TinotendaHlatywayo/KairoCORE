<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->string('action', 60);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['entity_type', 'entity_id']);
        });

        $merging = DB::table('departments')
            ->where('code', 'FIN')
            ->get(['id', 'permissions']);

        foreach ($merging as $department) {
            $current = is_array($department->permissions) ? $department->permissions : (json_decode((string) $department->permissions, true) ?: []);
            if (in_array('finance.manage_student_financial_history', $current, true)) {
                continue;
            }
            $current[] = 'finance.manage_student_financial_history';
            DB::table('departments')
                ->where('id', $department->id)
                ->update(['permissions' => json_encode(array_values(array_unique($current)))]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audit_logs');
    }
};