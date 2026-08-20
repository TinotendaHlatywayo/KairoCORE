<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing SchoolCore reporting tables for the Enterprise Reporting Engine.
 *
 * This migration is additive — it never drops existing columns or tables, so the
 * original 4-step report generator and its templates keep working. New columns are
 * nullable / defaulted so pre-existing rows remain valid on the new engine (the
 * LegacyReportAdapter bridges them to the new config format at run time).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_report_templates', function (Blueprint $table) {
            // New-engine configuration (kept nullable so legacy rows are untouched).
            $table->string('report_category', 80)->nullable()->after('report_type'); // admissions|academics|...|system-audit
            $table->string('sharing_scope', 20)->default('private')->after('layout_settings'); // private|department|school
            $table->unsignedBigInteger('department_id')->nullable()->after('sharing_scope');
            $table->json('datasets')->nullable()->after('department_id');      // ["students.register", "finance.invoice"]
            $table->json('joins')->nullable()->after('datasets');               // enabled relation edges
            $table->json('filters')->nullable()->after('joins');                // AND/OR condition rows
            $table->json('grouping')->nullable()->after('filters');             // group-by field keys
            $table->json('calculations')->nullable()->after('grouping');        // aggregate/formula rows
            $table->json('sorting')->nullable()->after('calculations');         // [{field, direction}]
            $table->json('visualizations')->nullable()->after('sorting');       // chart configs
            $table->boolean('is_system')->default(false)->after('is_favorite'); // shipped enterprise templates
            $table->unsignedInteger('config_version')->default(1)->after('is_system'); // 1 = legacy, 2 = engine
            $table->unsignedInteger('version')->default(1)->after('config_version');   // template version history
            $table->timestamp('last_run_at')->nullable()->after('version');
            $table->unsignedBigInteger('last_edited_by_id')->nullable()->after('last_run_at');

            $table->index(['sharing_scope'], 'idx_ert_sharing_scope');
            $table->index(['report_category'], 'idx_ert_report_category');
        });

        Schema::table('enterprise_report_schedules', function (Blueprint $table) {
            $table->string('distribution_method', 20)->default('email')->after('frequency'); // email|notification|both
            $table->string('output_format', 10)->default('pdf')->after('distribution_method');
            $table->boolean('generate_on_demand')->default(false)->after('output_format');
        });

        Schema::table('generated_reports', function (Blueprint $table) {
            $table->json('summary')->nullable()->after('record_count');   // totals / aggregates snapshot
            $table->json('filters_used')->nullable()->after('summary');    // runtime filters snapshot
            $table->boolean('is_downloaded')->default(false)->after('filters_used');
        });

        // Dashboards (user-assembled executive views).
        Schema::create('report_dashboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('layout_grid', 20)->default('12');       // 12-column responsive grid
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['school_id', 'name'], 'uq_report_dashboards_school_name');
        });

        Schema::create('report_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('report_dashboard_id');
            $table->unsignedBigInteger('enterprise_report_template_id')->nullable(); // report-driven widget
            $table->string('widget_type', 30)->default('chart');      // kpi|chart|table|text|calendar
            $table->string('title')->nullable();
            $table->string('viz_type', 30)->nullable();               // bar|line|pie|... (chart widgets)
            $table->string('dataset_key', 80)->nullable();            // direct KPI from a dataset
            $table->string('field_key', 120)->nullable();             // KPI field to aggregate
            $table->string('aggregate', 20)->nullable();              // count|sum|average|min|max
            $table->json('config')->nullable();                       // colors, filters, extra options
            // MySQL strict mode disallows literal defaults on JSON columns, so
            // use the expression-default syntax (supported since MySQL 8.0.13).
            $table->json('position')->default(DB::raw("('{\"x\":0,\"y\":0,\"w\":4,\"h\":4}')")); // grid coordinates
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('report_dashboard_id', 'fk_widget_dashboard_id')->references('id')->on('report_dashboards')->onDelete('cascade');
            $table->foreign('enterprise_report_template_id', 'fk_widget_report_id')->references('id')->on('enterprise_report_templates')->onDelete('set null');
        });

        // Version history for saved report templates.
        Schema::create('report_template_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('enterprise_report_template_id');
            $table->unsignedInteger('version');
            $table->json('snapshot'); // full template state (minus id/timestamps)
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('enterprise_report_template_id', 'fk_rpt_ver_template_id')->references('id')->on('enterprise_report_templates')->onDelete('cascade');
            $table->unique(['enterprise_report_template_id', 'version'], 'uq_rpt_ver_template_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_template_versions');
        Schema::dropIfExists('report_dashboard_widgets');
        Schema::dropIfExists('report_dashboards');

        Schema::table('generated_reports', function (Blueprint $table) {
            $table->dropColumn(['summary', 'filters_used', 'is_downloaded']);
        });
        Schema::table('enterprise_report_schedules', function (Blueprint $table) {
            $table->dropColumn(['distribution_method', 'output_format', 'generate_on_demand']);
        });
        Schema::table('enterprise_report_templates', function (Blueprint $table) {
            $table->dropColumn([
                'report_category', 'sharing_scope', 'department_id', 'datasets', 'joins', 'filters',
                'grouping', 'calculations', 'sorting', 'visualizations', 'is_system',
                'config_version', 'version', 'last_run_at', 'last_edited_by_id',
            ]);
        });
    }
};
