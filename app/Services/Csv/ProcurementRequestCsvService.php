<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Inventory\Models\ProcurementRequest;

class ProcurementRequestCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'request_number' => [
                'label' => __('Request Number'),
                'required' => true,
                'guesses' => ['Request Number', 'PR Number'],
                'example' => 'PR-2026-0001',
            ],
            'requester' => [
                'label' => __('Requested By'),
                'required' => true,
                'guesses' => ['Requested By', 'Requester'],
                'example' => 'Tendai Mutasa',
            ],
            'department' => [
                'label' => __('Department'),
                'required' => false,
                'guesses' => ['Department'],
                'example' => 'Academics',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'draft',
                'default' => 'draft',
                'in' => ['draft', 'pending_approval', 'approved', 'rejected'],
            ],
            'urgency' => [
                'label' => __('Urgency'),
                'required' => false,
                'guesses' => ['Urgency'],
                'example' => 'medium',
                'default' => 'medium',
                'in' => ['low', 'medium', 'high', 'emergency'],
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes'],
                'example' => 'Requisition for term one stationery.',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Request Number', 'Requested By', 'Department', 'Status', 'Urgency', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = ProcurementRequest::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('requester')
            ->orderBy('id');

        $lastId = 0;

        do {
            $requests = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($requests->isEmpty()) {
                break;
            }

            foreach ($requests as $request) {
                yield [
                    $request->request_number,
                    $request->requester?->name,
                    $request->department_id,
                    $request->status,
                    $request->urgency,
                    $request->notes,
                ];
            }

            $lastId = $requests->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'existingRequestNumbers' => ProcurementRequest::withoutTenantScope()->where('school_id', $schoolId)->pluck('request_number')
                ->map(fn ($v): string => strtolower(trim((string) $v)))->flip(),
            'usersByName' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($u): string => strtolower(trim($u->name))),
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

        foreach (['request_number', 'requester'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $requestNumber = strtolower(trim($data['request_number'] ?? ''));
        if ($requestNumber !== '' && isset($lookups['existingRequestNumbers'][$requestNumber])) {
            $errors[] = 'Request Number ['.$data['request_number'].'] already exists for another request in this school.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['draft', 'pending_approval', 'approved', 'rejected'], true)) {
            $errors[] = 'Status must be one of: draft, pending_approval, approved, rejected.';
        }

        $data['urgency'] = strtolower(trim($data['urgency'] ?? ''));
        if ($data['urgency'] !== '' && ! in_array($data['urgency'], ['low', 'medium', 'high', 'emergency'], true)) {
            $errors[] = 'Urgency must be one of: low, medium, high, emergency.';
        }

        $requester = trim($data['requester'] ?? '');
        $data['_requester'] = $requester !== '' ? ($lookups['usersByName'][strtolower($requester)] ?? null) : null;
        if ($requester !== '' && ! $data['_requester']) {
            $errors[] = 'Requested By ['.$requester.'] does not match any user account in this school.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        ProcurementRequest::create([
            'school_id' => $schoolId,
            'request_number' => $data['request_number'],
            'requester_id' => $data['_requester']->id,
            'department_id' => $data['department'] !== '' ? $data['department'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'draft',
            'urgency' => $data['urgency'] !== '' ? $data['urgency'] : 'medium',
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        $lookups['existingRequestNumbers'][strtolower(trim($data['request_number']))] = true;
    }
}
