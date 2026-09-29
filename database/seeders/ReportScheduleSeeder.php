<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\School as SchoolModel;
use App\Models\User as UserModel;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Support\ReportPresetCatalogue;

/**
 * Seeds the shipped "system" enterprise report templates (config_version = 2)
 * plus example schedules, so every tenant starts with useful, engine-ready
 * reports that can be customised or copied by end users.
 *
 * The preset definitions live in {@see ReportPresetCatalogue} so the generator's
 * preset cards and the contract test read exactly the same configs.
 */
class ReportScheduleSeeder extends Seeder
{
    public function run(?int $schoolId = null, ?int $createdBy = null): void
    {
        $schoolId = $schoolId ? (int) $schoolId : $this->resolveSchoolId();

        if (! $schoolId) {
            $this->command?->warn('No school found — skipping report presets.');

            return;
        }

        $this->seedTenant($schoolId, $createdBy);
    }

    /**
     * Install presets into one tenant.
     *
     * Reached from `run()` with a resolved school, and per-school from
     * `schoolcore:install-report-presets`, which is what the deploy script
     * calls. Presets are keyed by (school, name), so running it repeatedly
     * refreshes existing presets in place rather than duplicating them.
     */
    protected function seedTenant(int $schoolId, ?int $createdBy = null): void
    {
        $createdBy = $createdBy ?? $this->resolveCreatedBy($schoolId);
        $templates = ReportPresetCatalogue::all();

        foreach ($templates as $template) {
            // Only the engine config is persisted. The catalogue's presentation
            // keys (icon, tone, description) belong to the picker, not to a
            // stored template, and `array_merge` would happily write them into
            // every tenant's template row.
            $attributes = collect($template)
                ->except(['icon', 'tone', 'description', 'category_label', 'recommended', 'field_count', 'dataset_count'])
                ->all();

            EnterpriseReportTemplate::updateOrCreate(
                ['school_id' => $schoolId, 'name' => $template['name']],
                array_merge($attributes, [
                    'config_version' => 2,
                    'is_system' => true,
                    'layout_settings' => ReportPresetCatalogue::layoutSettings(),
                    'created_by_id' => $createdBy,
                ])
            );
        }

        $this->seedSchedules($schoolId);
    }

    /**
     * The tenant to install presets into when none is given explicitly.
     *
     * Previously hard-coded to school 6, so `db:seed` silently wrote 30
     * templates for a tenant that may not exist while the tenant actually being
     * worked in received nothing. Falls back to the first school so a plain
     * `db:seed` does something useful; pass an explicit id to target a tenant.
     */
    protected function resolveSchoolId(): ?int
    {
        if (app()->bound('current_tenant') && app('current_tenant')) {
            return (int) app('current_tenant')->id;
        }

        if (! Schema::hasTable('schools')) {
            return null;
        }

        $schoolId = SchoolModel::query()->orderBy('id')->value('id');

        return $schoolId ? (int) $schoolId : null;
    }

    /**
     * Attribute the installed presets to a real user of the tenant, so the
     * template library does not point at a user id that does not exist.
     */
    protected function resolveCreatedBy(int $schoolId): ?int
    {
        if (! $schoolId || ! Schema::hasTable('users')) {
            return null;
        }

        return UserModel::query()
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->value('id');
    }

    protected function seedSchedules(int $schoolId): void
    {
        $templateIdFor = fn (string $name) => EnterpriseReportTemplate::where('school_id', $schoolId)
            ->where('name', $name)->value('id');

        // Example schedule for the defaulter report.
        ReportSchedule::updateOrCreate(
            ['school_id' => $schoolId, 'name' => 'Monthly Defaulters Distribution'],
            [
                'enterprise_report_template_id' => $templateIdFor('Fee Defaulters (Cross-Module)'),
                'frequency' => 'monthly',
                'distribution_method' => 'email',
                'output_format' => 'pdf',
                'generate_on_demand' => true,
                'recipients' => ['bursar@example.com'],
                'filter_overrides' => [],
                'is_active' => true,
                'next_run_at' => now()->addMonth(),
            ]
        );

        ReportSchedule::updateOrCreate(
            ['school_id' => $schoolId, 'name' => 'Weekly Attendance Health'],
            [
                'enterprise_report_template_id' => $templateIdFor('Attendance by Class Stream'),
                'frequency' => 'weekly',
                'distribution_method' => 'email',
                'output_format' => 'pdf',
                'generate_on_demand' => true,
                'recipients' => ['headteacher@example.com'],
                'filter_overrides' => [],
                'is_active' => true,
                'next_run_at' => now()->addWeek(),
            ]
        );

        ReportSchedule::updateOrCreate(
            ['school_id' => $schoolId, 'name' => 'Monthly Revenue Roll-up'],
            [
                'enterprise_report_template_id' => $templateIdFor('Monthly Revenue Collections'),
                'frequency' => 'monthly',
                'distribution_method' => 'email',
                'output_format' => 'xls',
                'generate_on_demand' => true,
                'recipients' => ['finance@example.com'],
                'filter_overrides' => [],
                'is_active' => true,
                'next_run_at' => now()->addMonth(),
            ]
        );
    }
}
