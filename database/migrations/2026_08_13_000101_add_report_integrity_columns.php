<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data-accuracy audit columns for generated reports: how long a query took,
 * a content checksum over the exported rows, and the last verification result
 * so operators can prove a report reflects current source data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('generated_reports', 'execution_ms')) {
                $table->unsignedInteger('execution_ms')->nullable()->after('record_count');
            }

            if (! Schema::hasColumn('generated_reports', 'data_checksum')) {
                $table->string('data_checksum', 64)->nullable()->after('execution_ms');
            }

            if (! Schema::hasColumn('generated_reports', 'data_validated')) {
                $table->boolean('data_validated')->default(false)->after('data_checksum');
            }

            if (! Schema::hasColumn('generated_reports', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('data_validated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_reports', function (Blueprint $table) {
            foreach (['execution_ms', 'data_checksum', 'data_validated', 'validated_at'] as $column) {
                if (Schema::hasColumn('generated_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
