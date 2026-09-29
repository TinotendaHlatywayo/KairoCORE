<?php

namespace Tests\Feature;

use App\Filament\App\Pages\ReportGeneratorPage;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Services\DatasetRegistry;
use Modules\Reports\Support\ReportPresetCatalogue;

class ReportGeneratorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $this->school = School::find(5);
        app()->instance('current_tenant', $this->school);
        URL::defaults(['tenant' => $this->school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    /**
     * A tenant user with reports access. The page's canAccess() is a module
     * visibility check, so the acting user mainly needs a resolvable school.
     */
    protected function actAsTenantUser(): User
    {
        $user = User::where('school_id', $this->school->id)->firstOrFail();
        session(['current_tenant' => $this->school]);

        $this->actingAs($user);

        return $user;
    }

    public function test_page_renders_the_preset_grid_instead_of_a_wizard(): void
    {
        $this->actAsTenantUser();

        Livewire::test(ReportGeneratorPage::class)
            ->assertOk()
            ->assertSee(__('Start from a report'))
            ->assertSee(__('Build a custom report'))
            // No wizard markers: the multi-step flow is gone.
            ->assertDontSee('fi-wizard', false);
    }

    public function test_every_catalogue_preset_renders_as_a_card(): void
    {
        $this->actAsTenantUser();

        $component = Livewire::test(ReportGeneratorPage::class);

        foreach (ReportPresetCatalogue::all() as $preset) {
            $component->assertSee(e($preset['name']));
        }
    }

    public function test_category_filter_narrows_the_cards(): void
    {
        $this->actAsTenantUser();

        $component = Livewire::test(ReportGeneratorPage::class)
            ->call('setPresetCategory', 'finance');

        $shown = collect($component->instance()->getPresets());
        $this->assertNotEmpty($shown);
        $this->assertSame(['finance'], $shown->pluck('report_category')->unique()->values()->all());

        // A Finance preset shows, a non-Finance one does not.
        $finance = $shown->firstWhere('report_category', 'finance');
        $library = collect(ReportPresetCatalogue::all())
            ->first(fn (array $p) => ($p['report_category'] ?? null) === 'library');

        $component->assertSee(e($finance['name']));
        $component->assertDontSee(e($library['name']));
    }

    public function test_applying_a_preset_fills_the_form(): void
    {
        $this->actAsTenantUser();

        $preset = ReportPresetCatalogue::findByName('Student Register by Class Stream');

        $component = Livewire::test(ReportGeneratorPage::class)
            ->call('applyPreset', 'Student Register by Class Stream')
            ->assertSet('activePreset', 'Student Register by Class Stream');

        $this->assertSame($preset['datasets'], $component->get('data.datasets'));
        $this->assertSame($preset['selected_fields'], $component->get('data.selected_fields'));
        $this->assertSame('Student Register by Class Stream', $component->get('data.template_name'));
        $this->assertSame($preset['orientation'], $component->get('data.orientation'));
    }

    public function test_a_multi_dataset_preset_hydrates_its_joins_as_edge_keys(): void
    {
        $this->actAsTenantUser();

        $preset = collect(ReportPresetCatalogue::forPicker())
            ->first(fn (array $p) => $p['dataset_count'] > 1);

        $this->assertNotNull($preset, 'The catalogue should ship at least one multi-dataset preset.');

        $component = Livewire::test(ReportGeneratorPage::class)
            ->call('applyPreset', $preset['name']);

        $expected = array_map(
            fn (array $edge) => "{$edge['from']}::{$edge['to']}",
            $preset['joins']
        );

        $this->assertSame($expected, $component->get('data.joins'));

        // Every hydrated edge must correspond to a real relationship, otherwise
        // the planner would silently drop it and the report would lose a source.
        $registry = app(DatasetRegistry::class);

        foreach ($expected as $key) {
            [$from, $to] = explode('::', $key, 2);
            $this->assertNotNull($registry->byKey($from), "Unknown dataset {$from}");
            $this->assertNotNull($registry->byKey($to), "Unknown dataset {$to}");
        }
    }

    public function test_preset_calculations_hydrate_into_dataset_and_field_parts(): void
    {
        $this->actAsTenantUser();

        $preset = collect(ReportPresetCatalogue::forPicker())
            ->first(fn (array $p) => ! empty($p['calculations']));

        $this->assertNotNull($preset);

        $component = Livewire::test(ReportGeneratorPage::class)
            ->call('applyPreset', $preset['name']);

        $this->assertCount(count($preset['calculations']), $component->get('data.calculations'));

        foreach ($component->get('data.calculations') as $calc) {
            $this->assertNotEmpty($calc['dataset'], 'A hydrated calculation must carry a dataset.');
            $this->assertNotEmpty($calc['field'], 'A hydrated calculation must carry a field.');
        }
    }

    public function test_custom_report_clears_a_loaded_preset(): void
    {
        $this->actAsTenantUser();

        Livewire::test(ReportGeneratorPage::class)
            ->call('applyPreset', 'Overdue Books')
            ->assertSet('activePreset', 'Overdue Books')
            ->call('startCustomReport')
            ->assertSet('activePreset', null)
            ->assertSet('data.datasets', [])
            ->assertSet('data.selected_fields', []);
    }

    public function test_applying_an_unknown_preset_is_a_no_op(): void
    {
        $this->actAsTenantUser();

        Livewire::test(ReportGeneratorPage::class)
            ->call('applyPreset', 'This Report Does Not Exist')
            ->assertSet('activePreset', null);
    }

    public function test_submit_only_persists_a_template_after_a_successful_run(): void
    {
        $user = $this->actAsTenantUser();
        $name = 'Generator Test '.uniqid();

        $before = EnterpriseReportTemplate::where('school_id', $this->school->id)->count();

        Livewire::test(ReportGeneratorPage::class)
            ->fillForm([
                'template_name' => $name,
                'datasets' => ['students.register'],
                'selected_fields' => [
                    'students.register.full_name',
                    'students.register.class_name',
                ],
                'output_format' => 'csv',
            ])
            ->call('submit');

        $after = EnterpriseReportTemplate::where('school_id', $this->school->id)->count();
        $this->assertSame($before + 1, $after, 'A successful run must store its template.');

        $template = EnterpriseReportTemplate::where('school_id', $this->school->id)
            ->where('name', $name)
            ->first();

        $this->assertNotNull($template);
        $this->assertNotNull($template->last_run_at, 'execute() should stamp the stored template.');
        $this->assertNotNull(
            $template->generatedReports()->first(),
            'The generated report must be linked back to the template it came from.'
        );

        $template->generatedReports()->each(fn ($report) => $report->delete());
        $template->delete();
        unset($user);
    }

    public function test_the_original_cross_aggregate_bug_is_repaired_not_saved_broken(): void
    {
        $this->actAsTenantUser();
        $name = 'Generator Repaired '.uniqid();

        // This is the exact combination that used to throw
        // "Unknown column 'finance_revenue_summary.month'". The planner now
        // drops the unreachable aggregate, so the run must succeed and the
        // stored template must carry the repaired config, not the broken one.
        Livewire::test(ReportGeneratorPage::class)
            ->fillForm([
                'template_name' => $name,
                'datasets' => ['finance.invoice', 'finance.revenue_summary'],
                'selected_fields' => [
                    'finance.invoice.invoice_number',
                    'finance.revenue_summary.month',
                ],
                'output_format' => 'csv',
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $template = EnterpriseReportTemplate::where('school_id', $this->school->id)
            ->where('name', $name)
            ->first();

        $this->assertNotNull($template, 'A repaired config is still a working report and should be kept.');

        $config = app(\Modules\Reports\Services\LegacyAdapter::class)->normalize($template);

        $this->assertNotContains(
            'finance.revenue_summary',
            $config['datasets'],
            'The unreachable dataset must not be persisted.'
        );

        foreach ($config['selected_fields'] as $field) {
            $this->assertStringStartsNotWith(
                'finance.revenue_summary.',
                $field,
                'Fields belonging to a dropped dataset must not be persisted.'
            );
        }

        $template->generatedReports()->each(fn ($report) => $report->delete());
        $template->delete();
    }

    public function test_submit_does_not_persist_a_template_when_the_run_cannot_produce_an_artifact(): void
    {
        $this->actAsTenantUser();
        $name = 'Generator Failed '.uniqid();

        $before = EnterpriseReportTemplate::where('school_id', $this->school->id)->count();

        // The query is valid but the artifact write is not: ExportService throws
        // on an unsupported format. This is the case that used to leave a
        // template behind and fail again on every subsequent run.
        Livewire::test(ReportGeneratorPage::class)
            ->fillForm([
                'template_name' => $name,
                'datasets' => ['students.register'],
                'selected_fields' => ['students.register.full_name'],
                'output_format' => 'not-a-real-format',
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(
            $before,
            EnterpriseReportTemplate::where('school_id', $this->school->id)->count(),
            'A run that cannot produce an artifact must not leave a template behind.'
        );

        $this->assertNull(
            EnterpriseReportTemplate::where('school_id', $this->school->id)->where('name', $name)->first()
        );
    }
}
