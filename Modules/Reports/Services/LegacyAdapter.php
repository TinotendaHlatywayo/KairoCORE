<?php

namespace Modules\Reports\Services;

use Modules\Reports\Models\EnterpriseReportTemplate;

/**
 * Bridges legacy (config_version = 1) templates — saved by the original 4-step
 * generator — onto the new declarative engine config (config_version = 2), so
 * every saved layout keeps working and gains filters, grouping, visuals etc.
 */
class LegacyAdapter
{
    /**
     * Legacy `module.report_type` → engine dataset key + field remapping.
     */
    protected const MAPPING = [
        'students.student_register' => [
            'dataset' => 'students.register',
            'fields' => [
                'student_id_number' => 'student_id_number',
                'admission_number' => 'admission_number',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'gender' => 'gender',
                'date_of_birth' => 'date_of_birth',
                'class_name' => 'class_name',
                'boarding_status' => 'boarding_status',
                'status' => 'status',
                'admission_date' => 'admission_date',
                'national_id' => 'national_id',
            ],
        ],
        'finance.fee_balances' => [
            'dataset' => 'finance.invoice',
            'fields' => [
                'student_name' => 'student_name',
                'class_name' => 'class_name',
                'invoice_number' => 'invoice_number',
                'invoice_amount' => 'total_amount',
                'paid_amount' => 'paid_amount',
                'balance_due' => 'balance_amount',
                'due_date' => 'due_date',
            ],
        ],
        'inventory.stock_levels' => [
            'dataset' => 'inventory.item',
            'fields' => [
                'sku' => 'sku',
                'name' => 'name',
                'item_type' => 'item_type',
                'unit_of_measure' => 'unit_of_measure',
                'current_quantity' => 'current_quantity',
                'reorder_level' => 'reorder_level',
                'average_unit_cost' => 'average_unit_cost',
            ],
        ],
        'hostels.hostel_occupancy' => [
            'dataset' => 'hostel.allocation',
            'fields' => [
                'student_name' => 'student_name',
                'hostel_name' => 'hostel_name',
                'building_name' => 'building_name',
                'room_number' => 'room_number',
                'bed_number' => 'bed_number',
                'allocation_status' => 'status',
            ],
        ],
    ];

    public function __construct(protected DatasetRegistry $registry) {}

    /**
     * Detect whether a template needs bridging.
     */
    public function needsBridging(EnterpriseReportTemplate $template): bool
    {
        $version = $template->getAttribute('config_version');

        return $version === null || (int) $version === 1;
    }

    /**
     * Normalize a template into the engine config array (v2) regardless of its
     * stored version. Templates already at v2 are returned as-is.
     */
    public function normalize(EnterpriseReportTemplate $template): array
    {
        if (! $this->needsBridging($template)) {
            return [
                'datasets' => $template->datasets ?? [],
                'joins' => $template->joins ?? [],
                'selected_fields' => $template->selected_fields ?? [],
                'filters' => $template->filters ?? [],
                'grouping' => $template->grouping ?? [],
                'calculations' => $template->calculations ?? [],
                'sorting' => $template->sorting ?? [],
                'visualizations' => $template->visualizations ?? [],
            ];
        }

        $legacy = "{$template->module}.{$template->report_type}";
        $mapping = self::MAPPING[$legacy] ?? null;

        if (! $mapping) {
            throw new \InvalidArgumentException("No engine dataset registered for legacy report [{$legacy}].");
        }

        $datasetKey = $mapping['dataset'];
        $qualified = [];

        foreach ((array) $template->selected_fields as $legacyField) {
            if (isset($mapping['fields'][$legacyField])) {
                $qualified[] = "{$datasetKey}.{$mapping['fields'][$legacyField]}";
            }
        }

        if (empty($qualified)) {
            // Fall back to all provider fields for that dataset so the report still runs.
            foreach ($this->registry->byKey($datasetKey)['fields'] ?? [] as $field) {
                $qualified[] = "{$datasetKey}.{$field['key']}";
            }
        }

        return [
            'datasets' => [$datasetKey],
            'joins' => [],
            'selected_fields' => $qualified,
            'filters' => [],
            'grouping' => [],
            'calculations' => [],
            'sorting' => [],
            'visualizations' => [],
        ];
    }

    /**
     * Migrate a legacy row in place to config_version 2 (idempotent).
     */
    public function upgradeTemplate(EnterpriseReportTemplate $template): void
    {
        if (! $this->needsBridging($template)) {
            return;
        }

        $config = $this->normalize($template);

        $template->update([
            'datasets' => $config['datasets'],
            'joins' => $config['joins'],
            'selected_fields' => $config['selected_fields'],
            'filters' => $config['filters'],
            'grouping' => $config['grouping'],
            'calculations' => $config['calculations'],
            'sorting' => $config['sorting'],
            'visualizations' => $config['visualizations'],
            'config_version' => 2,
        ]);
    }
}
