<?php

namespace App\Services\Csv;

use Carbon\Carbon;
use Modules\Academics\Models\AcademicYear;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;
use Modules\Hostels\Models\HostelBuilding;
use Modules\Hostels\Models\HostelFloor;
use Modules\Hostels\Models\HostelRoom;
use Modules\Hostels\Models\HostelWing;
use Modules\Students\Models\Student;

class HostelAllocationCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'student' => [
                'label' => __('Student Admission No.'),
                'required' => true,
                'guesses' => ['Student', 'Admission No', 'Student Admission Number'],
                'example' => 'STU-2026-001',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => true,
                'guesses' => ['Academic Year', 'Year'],
                'example' => '2026',
            ],
            'hostel' => [
                'label' => __('Hostel Name'),
                'required' => true,
                'guesses' => ['Hostel', 'Hostel Name'],
                'example' => 'Mbare Hostel',
            ],
            'building' => [
                'label' => __('Building Name'),
                'required' => true,
                'guesses' => ['Building', 'Building Name'],
                'example' => 'Block A',
            ],
            'floor_number' => [
                'label' => __('Floor Number'),
                'required' => true,
                'guesses' => ['Floor Number', 'Floor'],
                'example' => '1',
            ],
            'wing' => [
                'label' => __('Wing Name'),
                'required' => true,
                'guesses' => ['Wing', 'Wing Name'],
                'example' => 'West Wing',
            ],
            'room_number' => [
                'label' => __('Room Number'),
                'required' => true,
                'guesses' => ['Room Number', 'Room No'],
                'example' => '101',
            ],
            'bed_number' => [
                'label' => __('Bed Number'),
                'required' => true,
                'guesses' => ['Bed Number', 'Bed No'],
                'example' => 'A-1',
            ],
            'allocated_at' => [
                'label' => __('Allocated Date'),
                'required' => false,
                'guesses' => ['Allocated Date', 'Date Allocated'],
                'example' => '2026-01-15',
                'date' => true,
            ],
            'expected_checkout_at' => [
                'label' => __('Expected Checkout'),
                'required' => false,
                'guesses' => ['Expected Checkout', 'Checkout Date'],
                'example' => '2026-12-15',
                'date' => true,
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'completed', 'cancelled', 'waiting_list'],
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes'],
                'example' => 'Allocated during boarding registration',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Student Admission No', 'Academic Year', 'Hostel Name', 'Building Name',
            'Floor Number', 'Wing Name', 'Room Number', 'Bed Number', 'Allocated Date',
            'Expected Checkout', 'Status', 'Notes',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = HostelAllocation::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['bed.room.wing.floor.building.hostel', 'student', 'academicYear'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $allocations = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($allocations->isEmpty()) {
                break;
            }

            foreach ($allocations as $allocation) {
                $room = $allocation->bed?->room;
                $floor = $room?->wing?->floor;
                $building = $floor?->building;

                yield [
                    $allocation->student?->admission_number,
                    $allocation->academicYear?->name,
                    $building?->hostel?->name,
                    $building?->name,
                    $floor?->floor_number,
                    $room?->wing?->name,
                    $room?->room_number,
                    $allocation->bed?->bed_number,
                    $allocation->allocated_at ?? '',
                    $allocation->expected_checkout_at ?? '',
                    $allocation->status,
                    $allocation->notes,
                ];
            }

            $lastId = $allocations->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $students = Student::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($s): string => strtolower(trim($s->admission_number)));

        $academicYears = AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($y): string => strtolower(trim($y->name)));

        $hostels = Hostel::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($h): string => strtolower(trim($h->name)));

        $buildings = HostelBuilding::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($b): string => $b->hostel_id.'::'.strtolower(trim($b->name)));

        $floors = HostelFloor::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($f): string => $f->building_id.'::'.trim((string) $f->floor_number));

        $wings = HostelWing::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($w): string => $w->floor_id.'::'.strtolower(trim($w->name)));

        $rooms = HostelRoom::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($r): string => $r->floor_id.'::'.strtolower(trim($r->room_number)));

        $beds = HostelBed::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($b): string => $b->room_id.'::'.strtolower(trim($b->bed_number)));

        $occupiedBeds = HostelBed::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('status', 'occupied')
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [$id => true]);

        $existingAllocations = HostelAllocation::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->get(['student_id', 'academic_year_id', 'status'])
            ->mapWithKeys(fn ($a): array => [$a->student_id.'::'.$a->academic_year_id.'::'.$a->status => true]);

        $lookups = [
            'students' => $students,
            'academicYears' => $academicYears,
            'hostels' => $hostels,
            'buildings' => $buildings,
            'floors' => $floors,
            'wings' => $wings,
            'rooms' => $rooms,
            'beds' => $beds,
            'occupiedBeds' => $occupiedBeds,
            'existingAllocations' => $existingAllocations,
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

        foreach (['student', 'academic_year', 'hostel', 'building', 'floor_number', 'wing', 'room_number', 'bed_number'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $studentNumber = strtolower($data['student'] ?? '');
        $data['_student'] = $studentNumber !== '' ? ($lookups['students'][$studentNumber] ?? null) : null;
        if ($studentNumber !== '' && ! $data['_student']) {
            $errors[] = 'Student ['.$data['student'].'] was not found in this school.';
        }

        $yearName = strtolower($data['academic_year'] ?? '');
        $data['_academicYear'] = $yearName !== '' ? ($lookups['academicYears'][$yearName] ?? null) : null;
        if ($yearName !== '' && ! $data['_academicYear']) {
            $errors[] = 'Academic Year ['.$data['academic_year'].'] was not found in this school. Available years: '.($lookups['academicYears']->pluck('name')->implode(', ') ?: 'none').'.';
        }

        $hostelName = strtolower($data['hostel'] ?? '');
        $data['_hostel'] = $hostelName !== '' ? ($lookups['hostels'][$hostelName] ?? null) : null;
        if ($hostelName !== '' && ! $data['_hostel']) {
            $errors[] = 'Hostel ['.$data['hostel'].'] was not found in this school. Available hostels: '.($lookups['hostels']->pluck('name')->implode(', ') ?: 'none').'.';
        }

        if ($data['_hostel']) {
            $buildingName = strtolower($data['building'] ?? '');
            $data['_building'] = $buildingName !== ''
                ? ($lookups['buildings'][$data['_hostel']->id.'::'.$buildingName] ?? null)
                : null;
            if ($buildingName !== '' && ! $data['_building']) {
                $errors[] = 'Building ['.$data['building'].'] was not found in Hostel ['.$data['_hostel']->name.'].';
            }
        }

        if (isset($data['_building']) && $data['_building']) {
            $floorKey = $data['_building']->id.'::'.trim($data['floor_number'] ?? '');
            $data['_floor'] = $lookups['floors'][$floorKey] ?? null;
            if (! $data['_floor']) {
                $errors[] = 'Floor Number ['.$data['floor_number'].'] was not found in Building ['.$data['_building']->name.'].';
            }
        }

        if (isset($data['_floor']) && $data['_floor']) {
            $wingName = strtolower($data['wing'] ?? '');
            $data['_wing'] = $wingName !== ''
                ? ($lookups['wings'][$data['_floor']->id.'::'.$wingName] ?? null)
                : null;
            if ($wingName !== '' && ! $data['_wing']) {
                $errors[] = 'Wing ['.$data['wing'].'] was not found in Floor ['.$data['_floor']->floor_number.'].';
            }
        }

        if (isset($data['_wing']) && $data['_wing'] && $data['room_number'] !== '') {
            $roomKey = $data['_wing']->floor_id.'::'.strtolower(trim($data['room_number']));
            $data['_room'] = $lookups['rooms'][$roomKey] ?? null;
            if (! $data['_room']) {
                $errors[] = 'Room Number ['.$data['room_number'].'] was not found in Wing ['.$data['_wing']->name.'].';
            }
        }

        if (isset($data['_room']) && $data['_room'] && $data['bed_number'] !== '') {
            $bedKey = $data['_room']->id.'::'.strtolower(trim($data['bed_number']));
            $data['_bed'] = $lookups['beds'][$bedKey] ?? null;
            if (! $data['_bed']) {
                $errors[] = 'Bed Number ['.$data['bed_number'].'] was not found in Room ['.$data['_room']->room_number.'].';
            }
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $data['status'] = 'active';
        }
        if (! in_array($data['status'], ['active', 'completed', 'cancelled', 'waiting_list'], true)) {
            $errors[] = 'Status must be one of: active, completed, cancelled, waiting_list.';
        }

        $allocatedAt = trim($data['allocated_at'] ?? '');
        if ($allocatedAt === '') {
            $data['allocated_at'] = now()->toDateString();
        } else {
            try {
                $data['allocated_at'] = Carbon::parse($allocatedAt)->toDateString();
            } catch (\Throwable) {
                $errors[] = 'Allocated Date ['.$allocatedAt.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        $checkout = trim($data['expected_checkout_at'] ?? '');
        $data['expected_checkout_at'] = '';
        if ($checkout !== '') {
            try {
                $data['expected_checkout_at'] = Carbon::parse($checkout)->toDateString();
            } catch (\Throwable) {
                $errors[] = 'Expected Checkout ['.$checkout.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        if ($data['_student'] && $data['_academicYear'] && $data['status'] !== '') {
            $dedupeKey = $data['_student']->id.'::'.$data['_academicYear']->id.'::'.$data['status'];
            if (isset($lookups['existingAllocations'][$dedupeKey])) {
                $errors[] = 'A '.$data['status'].' allocation already exists for Student ['.$data['student'].'] in Academic Year ['.$data['academic_year'].'].';
            }
        }

        if ($data['_bed']) {
            $isOccupied = $lookups['occupiedBeds'][$data['_bed']->id] ?? false;
            if (! in_array($data['status'], ['completed', 'cancelled'], true) && $isOccupied) {
                $errors[] = 'Bed ['.$data['bed_number'].'] in Room ['.$data['room_number'].'] is already occupied.';
            }
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $allocation = HostelAllocation::create([
            'school_id' => $schoolId,
            'student_id' => $data['_student']->id,
            'bed_id' => $data['_bed']->id,
            'academic_year_id' => $data['_academicYear']->id,
            'status' => $data['status'],
            'allocated_at' => $data['allocated_at'],
            'expected_checkout_at' => $data['expected_checkout_at'] !== '' ? $data['expected_checkout_at'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        $lookups['existingAllocations'][$data['_student']->id.'::'.$data['_academicYear']->id.'::'.$data['status']] = true;

        if (in_array($allocation->status, ['completed', 'cancelled'], true)) {
            unset($lookups['occupiedBeds'][$data['_bed']->id]);
        } elseif ($allocation->status === 'active') {
            $lookups['occupiedBeds'][$data['_bed']->id] = true;
        }
    }
}
