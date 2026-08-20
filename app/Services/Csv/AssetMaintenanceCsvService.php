<?php

namespace App\Services\Csv;

use Modules\Inventory\Models\AssetMaintenanceLog;
use Modules\Inventory\Models\FixedAsset;

class AssetMaintenanceCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'asset' => [
                'label' => __('Asset Number'),
                'required' => true,
                'guesses' => ['Asset', 'Asset Number', 'Fixed Asset'],
                'example' => 'FA-2026-0001',
            ],
            'title' => [
                'label' => __('Title'),
                'required' => true,
                'guesses' => ['Title', 'Maintenance Title'],
                'example' => 'Brake service',
            ],
            'type' => [
                'label' => __('Type'),
                'required' => false,
                'guesses' => ['Type'],
                'example' => 'preventive',
                'default' => 'preventive',
                'in' => ['preventive', 'corrective', 'calibration'],
            ],
            'schedule_type' => [
                'label' => __('Schedule Type'),
                'required' => false,
                'guesses' => ['Schedule Type'],
                'example' => 'one_time',
                'default' => 'one_time',
                'in' => ['one_time', 'recurring'],
            ],
            'recurrence_interval_days' => [
                'label' => __('Recurrence Interval Days'),
                'required' => false,
                'guesses' => ['Recurrence Interval Days', 'Interval Days'],
                'example' => '90',
            ],
            'scheduled_date' => [
                'label' => __('Scheduled Date'),
                'required' => true,
                'guesses' => ['Scheduled Date', 'Date'],
                'example' => '2026-08-01',
                'date' => true,
            ],
            'completed_date' => [
                'label' => __('Completed Date'),
                'required' => false,
                'guesses' => ['Completed Date', 'Date Completed'],
                'example' => '2026-08-15',
                'date' => true,
            ],
            'cost' => [
                'label' => __('Cost'),
                'required' => false,
                'guesses' => ['Cost', 'Amount'],
                'example' => '150.00',
                'default' => '0',
            ],
            'performed_by' => [
                'label' => __('Performed By'),
                'required' => false,
                'guesses' => ['Performed By', 'Technician'],
                'example' => 'AutoCare Garage',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'pending',
                'default' => 'pending',
                'in' => ['pending', 'in_progress', 'completed', 'overdue'],
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes'],
                'example' => 'Inspect brake pads and fluid levels.',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Asset Number', 'Title', 'Type', 'Schedule Type', 'Recurrence Interval Days',
            'Scheduled Date', 'Completed Date', 'Cost', 'Performed By', 'Status', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = AssetMaintenanceLog::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('fixedAsset')
            ->orderBy('id');

        $lastId = 0;

        do {
            $logs = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($logs->isEmpty()) {
                break;
            }

            foreach ($logs as $log) {
                yield [
                    $log->fixedAsset?->asset_number,
                    $log->title,
                    $log->type,
                    $log->schedule_type,
                    $log->recurrence_interval_days,
                    optional($log->scheduled_date)->format('Y-m-d'),
                    optional($log->completed_date)->format('Y-m-d'),
                    $log->cost,
                    $log->performed_by,
                    $log->status,
                    $log->notes,
                ];
            }

            $lastId = $logs->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'fixedAssets' => FixedAsset::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($a): string => strtolower(trim($a->asset_number))),
        ];

        return static::runImport(
            $filePath,
            $schoolId,
            $columnMap,
            $onProgress,
            $lookups,
            fn (array &$data, array $lookups) => static::validateAndNormalize($data, $lookups),
            fn (array $data, int $schoolId, array &$lookups) => static::createRow($data, $schoolId, $lookups),
        );
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        foreach (['asset', 'title', 'scheduled_date'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $assetNumber = trim($data['asset'] ?? '');
        $data['_fixedAsset'] = $assetNumber !== '' ? ($lookups['fixedAssets'][strtolower($assetNumber)] ?? null) : null;
        if ($assetNumber !== '' && ! $data['_fixedAsset']) {
            $errors[] = 'Asset Number ['.$assetNumber.'] was not found in this school. Available assets: '.($lookups['fixedAssets']->pluck('asset_number')->implode(', ') ?: 'none').'.';
        }

        $data['type'] = strtolower(trim($data['type'] ?? ''));
        if ($data['type'] !== '' && ! in_array($data['type'], ['preventive', 'corrective', 'calibration'], true)) {
            $errors[] = 'Type must be one of: preventive, corrective, calibration.';
        }

        $data['schedule_type'] = strtolower(trim($data['schedule_type'] ?? ''));
        if ($data['schedule_type'] !== '' && ! in_array($data['schedule_type'], ['one_time', 'recurring'], true)) {
            $errors[] = 'Schedule Type must be one of: one_time, recurring.';
        }

        $recurrence = trim($data['recurrence_interval_days'] ?? '');
        if ($data['schedule_type'] === 'recurring' && $recurrence === '') {
            $errors[] = 'Recurrence Interval Days is required when Schedule Type is recurring.';
        }

        if ($recurrence !== '' && ! is_numeric($recurrence)) {
            $errors[] = 'Recurrence Interval Days ['.$recurrence.'] must be a number.';
        }

        foreach (['scheduled_date', 'completed_date'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                continue;
            }

            $parsed = static::toDate($raw);

            if ($parsed === null) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data[$dateField] = $parsed;
            }
        }

        if ($data['scheduled_date'] !== '' && $data['completed_date'] !== ''
            && $data['completed_date'] < $data['scheduled_date']) {
            $errors[] = 'Completed Date ['.$data['completed_date'].'] must be on or after Scheduled Date ['.$data['scheduled_date'].'].';
        }

        $cost = trim($data['cost'] ?? '');
        if ($cost !== '' && ! is_numeric($cost)) {
            $errors[] = 'Cost ['.$cost.'] must be a number.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['pending', 'in_progress', 'completed', 'overdue'], true)) {
            $errors[] = 'Status must be one of: pending, in_progress, completed, overdue.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        AssetMaintenanceLog::create([
            'school_id' => $schoolId,
            'fixed_asset_id' => $data['_fixedAsset']->id,
            'title' => $data['title'],
            'type' => $data['type'] !== '' ? $data['type'] : 'preventive',
            'schedule_type' => $data['schedule_type'] !== '' ? $data['schedule_type'] : 'one_time',
            'recurrence_interval_days' => $data['recurrence_interval_days'] !== '' ? (int) $data['recurrence_interval_days'] : null,
            'scheduled_date' => $data['scheduled_date'],
            'completed_date' => $data['completed_date'] !== '' ? $data['completed_date'] : null,
            'cost' => $data['cost'] !== '' ? (float) $data['cost'] : 0,
            'performed_by' => $data['performed_by'] !== '' ? $data['performed_by'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'pending',
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
    }
}
