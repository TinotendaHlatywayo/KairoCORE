<?php

namespace App\Services\Csv;

use App\Models\User;
use Carbon\Carbon;
use Modules\Attendance\Models\StaffAttendance;

class StaffAttendanceCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'staff' => [
                'label' => __('Staff Name'),
                'required' => true,
                'guesses' => ['Staff', 'Staff Name', 'User'],
                'example' => 'Tendai Mutasa',
            ],
            'date' => [
                'label' => __('Date'),
                'required' => true,
                'guesses' => ['Date', 'Day'],
                'example' => '2026-07-01',
                'date' => true,
            ],
            'status' => [
                'label' => __('Status'),
                'required' => true,
                'guesses' => ['Status'],
                'example' => 'present',
                'in' => ['present', 'absent', 'late', 'half_day', 'excused'],
            ],
            'check_in_time' => [
                'label' => __('Check In Time'),
                'required' => false,
                'guesses' => ['Check In Time', 'Clock In'],
                'example' => '08:00',
            ],
            'check_out_time' => [
                'label' => __('Check Out Time'),
                'required' => false,
                'guesses' => ['Check Out Time', 'Clock Out'],
                'example' => '16:30',
            ],
            'method' => [
                'label' => __('Method'),
                'required' => false,
                'guesses' => ['Method'],
                'example' => 'manual',
                'default' => 'manual',
                'in' => ['manual', 'rfid', 'biometric', 'qr'],
            ],
            'marked_by' => [
                'label' => __('Marked By'),
                'required' => false,
                'guesses' => ['Marked By'],
                'example' => 'Tendai Mutasa',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Staff Name', 'Date', 'Status', 'Check In Time', 'Check Out Time',
            'Method', 'Marked By',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = StaffAttendance::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['user', 'markedBy'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $attendances = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($attendances->isEmpty()) {
                break;
            }

            foreach ($attendances as $attendance) {
                yield [
                    $attendance->user?->name,
                    optional($attendance->date)->format('Y-m-d'),
                    $attendance->status,
                    $attendance->check_in_time,
                    $attendance->check_out_time,
                    $attendance->method,
                    $attendance->markedBy?->name,
                ];
            }

            $lastId = $attendances->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $users = User::withoutTenantScope()->where('school_id', $schoolId)->get();

        $lookups = [
            'usersByName' => $users->keyBy(fn (User $u): string => strtolower(trim($u->name))),
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

        $data['staff'] = trim($data['staff'] ?? '');
        if ($data['staff'] === '') {
            $errors[] = 'Staff Name is required (column empty or not mapped).';
        }

        $rawDate = trim($data['date'] ?? '');
        if ($rawDate === '') {
            $data['date'] = '';
            $errors[] = 'Date is required (column empty or not mapped).';
        } else {
            try {
                $data['date'] = Carbon::parse($rawDate)->toDateString();
            } catch (\Throwable) {
                $data['date'] = '';
                $errors[] = 'Date ['.$rawDate.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $errors[] = 'Status is required (column empty or not mapped).';
        } elseif (! in_array($data['status'], ['present', 'absent', 'late', 'half_day', 'excused'], true)) {
            $errors[] = 'Status must be one of: present, absent, late, half_day, excused.';
        }

        foreach (['check_in_time', 'check_out_time'] as $timeField) {
            $raw = trim($data[$timeField] ?? '');

            if ($raw === '') {
                continue;
            }

            if (preg_match('/^\d{2}:\d{2}$/', $raw) !== 1) {
                $errors[] = static::columns()[$timeField]['label'].' ['.$raw.'] must be in HH:MM format.';
            }
        }

        $data['method'] = strtolower(trim($data['method'] ?? ''));
        if ($data['method'] === '') {
            $data['method'] = 'manual';
        }
        if (! in_array($data['method'], ['manual', 'rfid', 'biometric', 'qr'], true)) {
            $errors[] = 'Method must be one of: manual, rfid, biometric, qr.';
        }

        $data['_markedBy'] = null;
        $rawMarkedBy = trim($data['marked_by'] ?? '');
        if ($rawMarkedBy !== '') {
            $markedBy = static::resolveUser($rawMarkedBy, $lookups);

            if (! $markedBy) {
                $errors[] = 'Marked By ['.$rawMarkedBy.'] was not found in this school.';
            } else {
                $data['_markedBy'] = $markedBy;
            }
        }

        if (empty($errors) && $data['staff'] !== '') {
            $user = static::resolveUser($data['staff'], $lookups);

            if (! $user) {
                $errors[] = 'Staff ['.$data['staff'].'] was not found in this school.';
            } else {
                $data['_user'] = $user;
            }
        }

        return $errors;
    }

    protected static function resolveUser(string $value, array $lookups): ?User
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $lookups['usersByName'][strtolower($value)] ?? null;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        StaffAttendance::withoutTenantScope()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'user_id' => $data['_user']->id,
                'date' => $data['date'],
            ],
            [
                'status' => $data['status'],
                'check_in_time' => $data['check_in_time'] !== '' ? $data['check_in_time'] : null,
                'check_out_time' => $data['check_out_time'] !== '' ? $data['check_out_time'] : null,
                'method' => $data['method'],
                'marked_by_id' => $data['_markedBy']?->id,
            ],
        );
    }
}
