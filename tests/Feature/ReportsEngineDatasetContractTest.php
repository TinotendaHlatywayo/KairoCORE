<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Modules\Reports\Services\DatasetRegistry;
use Modules\Reports\Services\ReportQueryBuilder;
use Modules\Reports\Support\ReportPresetCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards the report engine's dataset and preset contracts.
 *
 * These assertions are the ones that catch the failure modes which produced
 * "Generation Run Failed" in production — a field referencing an alias the query
 * never introduces, or a preset selecting sources that cannot be joined. They
 * are deliberately database-free: they read the registry and the join planner,
 * so they run anywhere the test suite runs and cannot be masked by an empty
 * fixture database.
 */
class ReportsEngineDatasetContractTest extends TestCase
{
    protected function registry(): DatasetRegistry
    {
        return app(DatasetRegistry::class);
    }

    protected function builder(): ReportQueryBuilder
    {
        return app(ReportQueryBuilder::class);
    }

    /**
     * Every dataset definition is internally consistent.
     */
    #[Test]
    public function every_dataset_declares_a_key_alias_and_fields(): void
    {
        foreach ($this->registry()->all() as $dataset) {
            $this->assertArrayHasKey('key', $dataset, 'Dataset is missing a key.');
            $this->assertArrayHasKey('alias', $dataset, "Dataset [{$dataset['key']}] has no SQL alias.");
            $this->assertNotEmpty($dataset['fields'] ?? [], "Dataset [{$dataset['key']}] exposes no fields.");
            $this->assertTrue(
                isset($dataset['table']) || isset($dataset['baseSql']),
                "Dataset [{$dataset['key']}] has neither a table nor baseSql."
            );
        }
    }

    /**
     * A dataset's own fields must only reference aliases that dataset actually
     * brings into the query.
     *
     * This is the defect class behind the production `Unknown column
     * 'finance_revenue_summary.month'` failure: a field expression naming an
     * alias with no table behind it.
     */
    #[Test]
    public function every_field_references_an_alias_the_dataset_actually_joins(): void
    {
        foreach ($this->registry()->all() as $dataset) {
            $datasetKey = $dataset['key'];
            $available = [$dataset['alias']];

            foreach ($dataset['autoJoins'] ?? [] as $autoJoin) {
                $this->assertNotEmpty($autoJoin['alias'] ?? null, "Dataset [{$datasetKey}] has an autoJoin with no alias.");
                $available[] = $autoJoin['alias'];
            }

            foreach ($dataset['fields'] as $field) {
                $expression = (string) ($field['expression'] ?? "{$dataset['alias']}.{$field['key']}");

                foreach ($this->aliasesIn($expression) as $prefix) {
                    $this->assertContains(
                        $prefix,
                        $available,
                        "Field [{$field['key']}] of dataset [{$datasetKey}] references alias [{$prefix}], "
                            .'which the dataset never joins. Available: '.implode(', ', $available)
                    );
                }
            }
        }
    }

    /**
     * Every declared connection must be a single-hop relationship the planner
     * can emit as one equality.
     *
     * A connection whose target side names the target's own table is joinable in
     * that direction. One that reaches through a target autoJoin is two hops, and
     * the planner refuses it — which is correct, but it must not be declared as
     * if it were joinable, or a report that depends on it silently loses a
     * source.
     */
    #[Test]
    public function connections_only_point_at_targets_own_table(): void
    {
        foreach ($this->registry()->all() as $dataset) {
            foreach ($dataset['connections'] ?? [] as $connection) {
                $to = $connection['to'] ?? null;
                $target = is_string($to) ? $this->registry()->byKey($to) : null;

                $this->assertNotNull($target, "Dataset [{$dataset['key']}] connects to unknown dataset [{$to}].");

                foreach ($connection['to_fields'] ?? [] as $toField) {
                    $prefix = strstr((string) $toField, '.', true);

                    $this->assertSame(
                        $target['alias'],
                        $prefix,
                        "Connection [{$dataset['key']} → {$to}] targets [{$prefix}], which is not the target's own "
                            .'table. That is a two-hop relationship the engine cannot express as one join.'
                    );
                }

                foreach ($connection['from'] ?? [] as $fromField) {
                    $this->assertNotEmpty(
                        $this->aliasesIn((string) $fromField),
                        "Connection [{$dataset['key']} → {$to}] has an unparsable from field [{$fromField}]."
                    );
                }
            }
        }
    }

    /**
     * The default `default_order` each provider advertises must be real, or the
     * report silently comes back unordered.
     */
    #[Test]
    public function declared_default_order_points_at_a_real_field(): void
    {
        foreach ($this->registry()->all() as $dataset) {
            $order = $dataset['default_order'] ?? null;

            if (! is_string($order) || $order === '') {
                continue;
            }

            $field = strstr($order, '|', true) ?: $order;
            $keys = array_column($dataset['fields'], 'key');

            $this->assertContains(
                $field,
                $keys,
                "Dataset [{$dataset['key']}] declares default_order on unknown field [{$field}]."
            );
        }
    }

    /**
     * Datasets with no join path are dropped, not left in the config.
     *
     * The production failure was a config that selected sources with zero joins:
     * the fields survived into the SELECT list while their tables were never
     * joined, producing `Unknown column`. Dropping the unreachable dataset (and
     * its fields) is the repair.
     */
    #[Test]
    public function unjoinable_datasets_are_dropped_with_their_fields(): void
    {
        $builder = $this->builder();

        // The exact shape that produced the production failure: two sources from
        // the same module area selected together, with no relationship between
        // them. `finance.revenue_summary` is a per-month aggregate over payments,
        // so pairing it with per-invoice rows is a cross-product even if the
        // columns happened to line up.
        $this->assertNull(
            $builder->findConnection('finance.invoice', 'finance.revenue_summary'),
            'This test depends on invoices and the revenue summary being unrelated.'
        );

        $normalized = $builder->normalize([
            'datasets' => ['finance.invoice', 'finance.revenue_summary'],
            'joins' => [],
            'selected_fields' => [
                'finance.invoice.invoice_number',
                'finance.revenue_summary.month',
            ],
        ]);

        $this->assertSame(
            ['finance.invoice'],
            $normalized['datasets'],
            'A dataset with no relationship to the primary must be dropped, not carried into the SELECT list.'
        );

        $this->assertSame(
            ['finance.invoice.invoice_number'],
            $normalized['selected_fields'],
            'Fields belonging to a dropped dataset must be removed with it.'
        );

        $this->assertNotEmpty($builder->warnings(), 'Dropping a dataset must be reported to the user.');
    }

    /**
     * A config that selects a field nobody declares is repaired, not executed.
     */
    #[Test]
    public function unknown_fields_are_removed_and_reported(): void
    {
        $builder = $this->builder();

        $normalized = $builder->normalize([
            'datasets' => ['students.register'],
            'joins' => [],
            'selected_fields' => ['students.register.first_name', 'students.register.does_not_exist'],
        ]);

        $this->assertSame(['students.register.first_name'], $normalized['selected_fields']);
        $this->assertNotEmpty($builder->warnings());
    }

    /**
     * Two datasets may both expose `status`; both columns must survive.
     */
    #[Test]
    public function colliding_field_names_get_unique_output_aliases(): void
    {
        $builder = $this->builder();

        $normalized = $builder->normalize([
            'datasets' => ['students.register'],
            'joins' => [],
            'selected_fields' => ['students.register.first_name', 'students.register.status'],
        ]);

        $aliases = array_values($normalized['output_aliases']);

        $this->assertSame(
            count($aliases),
            count(array_unique($aliases)),
            'Every selected field must get a distinct SQL alias: '.implode(', ', $aliases)
        );
    }

    /**
     * A total over a column that is also selected must not overwrite it.
     */
    #[Test]
    public function a_calculation_cannot_collide_with_a_selected_field(): void
    {
        $builder = $this->builder();

        $normalized = $builder->normalize([
            'datasets' => ['finance.invoice'],
            'joins' => [],
            'selected_fields' => ['finance.invoice.total_amount'],
            'calculations' => [
                ['dataset' => 'finance.invoice', 'key' => 'total_amount', 'type' => 'sum', 'alias' => 'total_amount'],
            ],
        ]);

        $this->assertCount(1, $normalized['calculations'], 'The total must survive normalization.');

        $aliases = array_values($normalized['output_aliases']);

        $this->assertSame(
            count($aliases),
            count(array_unique($aliases)),
            'A total over a selected column must be renamed, not merged: '.implode(', ', $aliases)
        );
    }

    /**
     * A total declared in the stored `{dataset, key}` shape must be recognised.
     *
     * The stored shape is `{dataset, key}`; an older form produced
     * `{dataset, field}`. Both have to resolve, or a saved report silently loses
     * every one of its totals.
     */
    #[Test]
    public function calculations_are_recognised_in_both_stored_shapes(): void
    {
        foreach ([['key' => 'total_amount'], ['field' => 'total_amount']] as $shape) {
            $normalized = $this->builder()->normalize([
                'datasets' => ['finance.invoice'],
                'joins' => [],
                'selected_fields' => ['finance.invoice.invoice_number'],
                'calculations' => [array_merge(['dataset' => 'finance.invoice', 'type' => 'sum'], $shape)],
            ]);

            $this->assertCount(
                1,
                $normalized['calculations'],
                'A total declared with '.array_key_first($shape).' was dropped.'
            );
        }
    }

    /**
     * Sorting by a total references the total's alias, not a field, so it must
     * not be discarded for lacking a resolvable field key.
     */
    #[Test]
    public function sorting_by_a_total_survives_normalization(): void
    {
        $normalized = $this->builder()->normalize([
            'datasets' => ['finance.invoice'],
            'joins' => [],
            'selected_fields' => ['finance.invoice.invoice_number'],
            'calculations' => [
                ['dataset' => 'finance.invoice', 'key' => 'total_amount', 'type' => 'sum', 'alias' => 'revenue'],
            ],
            'sorting' => [['calculation' => 'revenue', 'direction' => 'desc']],
        ]);

        $this->assertCount(1, $normalized['sorting'], 'A sort by total must be preserved.');
        $this->assertSame('revenue', $normalized['sorting'][0]['calculation']);
    }

    /**
     * Selecting no field at all must fall back to the provider's defaults
     * instead of emitting an empty SELECT.
     */
    #[Test]
    public function a_config_without_fields_falls_back_to_defaults(): void
    {
        $builder = $this->builder();

        $normalized = $builder->normalize([
            'datasets' => ['students.register'],
            'joins' => [],
            'selected_fields' => [],
        ]);

        $this->assertNotEmpty($normalized['selected_fields']);
        $this->assertSame(
            $builder->defaultFieldsFor('students.register'),
            $normalized['selected_fields']
        );
    }

    /**
     * A related pair of datasets must be usable in at least one order.
     *
     * A connection is only expressible in the direction where both sides are in
     * scope when the JOIN is emitted, so some pairs only work one way round
     * (`hr.leave_request` joins onto `hr.department`, but not the reverse,
     * because the shared key sits on `hr.leave_request`'s enrichment table).
     * That is fine — but it must never be a dead end, or a user could tick two
     * related sources and get a report that silently loses one of them.
     */
    #[Test]
    public function every_related_dataset_pair_is_usable_in_at_least_one_order(): void
    {
        $builder = $this->builder();

        foreach ($this->registry()->keys() as $a) {
            foreach ($this->registry()->keys() as $b) {
                if ($a === $b || ! $this->declaredConnected($a, $b)) {
                    continue;
                }

                $this->assertTrue(
                    $builder->findConnection($a, $b) !== null || $builder->findConnection($b, $a) !== null,
                    "Datasets [{$a}] and [{$b}] declare a relationship, but neither order can express it as a join."
                );
            }
        }
    }

    /**
     * The planner must never invent a join between unrelated datasets.
     *
     * A join with no declared relationship is a cross-product: the row count
     * multiplies and every total in the report is wrong, with nothing in the
     * output to indicate it.
     */
    #[Test]
    public function the_planner_never_invents_a_join_between_unrelated_datasets(): void
    {
        $builder = $this->builder();

        foreach ($this->registry()->keys() as $a) {
            foreach ($this->registry()->keys() as $b) {
                if ($a === $b || $this->declaredConnected($a, $b)) {
                    continue;
                }

                $this->assertSame(
                    [],
                    $builder->resolveJoinPlan([$a, $b]),
                    "Datasets [{$a}] and [{$b}] declare no relationship, yet the planner invented a join."
                );
            }
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function datasetProvider(): array
    {
        $keys = app(DatasetRegistry::class)->keys();
        $cases = [];

        foreach ($keys as $key) {
            $cases[$key] = [$key];
        }

        return $cases;
    }

    /**
     * Every single dataset must build a runnable query with all of its fields.
     */
    #[Test]
    #[DataProvider('datasetProvider')]
    public function a_single_dataset_config_plans_successfully(string $datasetKey): void
    {
        $builder = $this->builder();

        $normalized = $builder->normalize([
            'datasets' => [$datasetKey],
            'joins' => [],
            'selected_fields' => $builder->defaultFieldsFor($datasetKey),
        ]);

        $this->assertSame([$datasetKey], $normalized['datasets']);
        $this->assertNotEmpty($normalized['selected_fields'], "Dataset [{$datasetKey}] planned no columns.");
    }

    /**
     * Every shipped preset must be a config the engine accepts as-is.
     *
     * A preset is shown to users as a one-click starting point, so anything the
     * planner has to repair is a preset that will surprise someone — and
     * anything it cannot repair is a preset that ships a broken template.
     */
    #[Test]
    public function every_shipped_preset_is_a_valid_engine_config(): void
    {
        $presets = ReportPresetCatalogue::all();

        $this->assertNotEmpty($presets, 'The preset catalogue is empty.');

        foreach ($presets as $preset) {
            $name = $preset['name'] ?? '(unnamed)';

            $this->assertNotEmpty($preset['datasets'] ?? [], "Preset [{$name}] selects no data source.");
            $this->assertNotEmpty($preset['selected_fields'] ?? [], "Preset [{$name}] selects no columns.");

            foreach ($preset['datasets'] as $datasetKey) {
                $this->assertNotNull(
                    $this->registry()->byKey($datasetKey),
                    "Preset [{$name}] references unknown dataset [{$datasetKey}]."
                );
            }

            foreach ($preset['selected_fields'] as $fieldKey) {
                $this->assertNotNull(
                    $this->builder()->fieldInfo($fieldKey),
                    "Preset [{$name}] references unknown field [{$fieldKey}]."
                );
            }

            foreach ($preset['grouping'] ?? [] as $groupKey) {
                $this->assertNotNull(
                    $this->builder()->fieldInfo($groupKey),
                    "Preset [{$name}] groups by unknown field [{$groupKey}]."
                );
            }
        }
    }

    /**
     * Preset names must be unique — they are the seeder's upsert key.
     */
    #[Test]
    public function preset_names_are_unique(): void
    {
        $names = array_column(ReportPresetCatalogue::all(), 'name');

        $this->assertSame(
            count($names),
            count(array_unique($names)),
            'Duplicate preset names: '.implode(', ', array_diff_assoc($names, array_unique($names)))
        );
    }

    /**
     * The catalogue's category filter must partition the presets.
     */
    #[Test]
    public function category_filters_cover_every_preset(): void
    {
        $total = 0;

        foreach (ReportPresetCatalogue::categories() as $category) {
            $total += count(ReportPresetCatalogue::forCategory($category));
        }

        $this->assertSame(
            count(ReportPresetCatalogue::all()),
            count(ReportPresetCatalogue::forCategory('all')),
            'The "all" filter must return the whole catalogue.'
        );

        $this->assertSame(
            count(ReportPresetCatalogue::all()),
            $total,
            'Every preset must belong to exactly one category.'
        );
    }

    /**
     * Every card the picker renders must carry displayable metadata.
     *
     * A missing or misspelled heroicon is not a cosmetic problem: Filament
     * throws while rendering the icon, which takes down the whole generator
     * page rather than just the card.
     */
    #[Test]
    public function every_picker_preset_has_rendering_metadata(): void
    {
        $presets = ReportPresetCatalogue::forPicker();

        $this->assertNotEmpty($presets, 'The picker catalogue is empty.');

        foreach ($presets as $preset) {
            $name = $preset['name'] ?? '(unnamed)';

            $this->assertNotEmpty($preset['icon'] ?? null, "Preset [{$name}] has no icon.");
            $this->assertNotEmpty($preset['tone'] ?? null, "Preset [{$name}] has no tone.");
            $this->assertNotEmpty($preset['category_label'] ?? null, "Preset [{$name}] has no category label.");
            $this->assertNotEmpty($preset['description'] ?? null, "Preset [{$name}] has no description.");

            $this->assertNotFalse(
                str_starts_with((string) $preset['icon'], 'heroicon-'),
                "Preset [{$name}] icon [{$preset['icon']}] is not a heroicon."
            );

            $this->assertTrue(
                is_file($this->heroiconPath((string) $preset['icon'])),
                "Preset [{$name}] icon [{$preset['icon']}] does not exist in the installed heroicons set."
            );

            $this->assertGreaterThan(0, $preset['field_count'] ?? 0, "Preset [{$name}] reports no columns.");
        }
    }

    /**
     * `forPicker()` decorates; `all()` must stay engine-only.
     *
     * The seeder persists `all()` verbatim, so a UI key leaking in would end up
     * stored in every tenant's template config.
     */
    #[Test]
    public function the_raw_catalogue_stays_free_of_ui_only_keys(): void
    {
        $uiKeys = ['icon', 'tone', 'description', 'category_label', 'recommended', 'field_count', 'dataset_count'];

        foreach (ReportPresetCatalogue::all() as $preset) {
            foreach ($uiKeys as $key) {
                $this->assertArrayNotHasKey(
                    $key,
                    $preset,
                    'UI key ['.$key.'] leaked into the engine config of preset ['.($preset['name'] ?? '?').'].'
                );
            }
        }
    }

    /**
     * Presentation-only decoration must never alter what gets executed.
     */
    #[Test]
    public function decorating_a_preset_does_not_change_its_engine_config(): void
    {
        foreach (ReportPresetCatalogue::all() as $index => $preset) {
            $decorated = ReportPresetCatalogue::forPicker('all')[$index] ?? null;

            $this->assertNotNull($decorated, 'Decoration lost a preset.');

            foreach (array_keys($preset) as $key) {
                $this->assertSame(
                    $preset[$key],
                    $decorated[$key] ?? null,
                    'Decoration changed engine key ['.$key.'] on preset ['.($preset['name'] ?? '?').'].'
                );
            }
        }
    }

    protected function heroiconPath(string $icon): string
    {
        return base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/'.str_replace('heroicon-', '', $icon).'.svg');
    }

    protected function declaredConnected(string $a, string $b): bool
    {
        foreach ([$a, $b] as $from) {
            $def = $this->registry()->byKey($from);

            foreach ($def['connections'] ?? [] as $connection) {
                if (($connection['to'] ?? null) === ($from === $a ? $b : $a)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The table aliases referenced in a SQL expression.
     *
     * @return array<int, string>
     */
    protected function aliasesIn(string $expression): array
    {
        preg_match_all('/\b([a-z][a-z0-9_]*)\.[a-z0-9_]+\b/i', $expression, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1] ?? [])));
    }
}
