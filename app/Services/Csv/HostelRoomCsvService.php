<?php

namespace App\Services\Csv;

use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelBuilding;
use Modules\Hostels\Models\HostelFloor;
use Modules\Hostels\Models\HostelRoom;
use Modules\Hostels\Models\HostelWing;

class HostelRoomCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
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
            'name' => [
                'label' => __('Room Name'),
                'required' => false,
                'guesses' => ['Room Name', 'Name'],
                'example' => 'West Wing 101',
            ],
            'room_type' => [
                'label' => __('Room Type'),
                'required' => false,
                'guesses' => ['Room Type', 'Type'],
                'example' => 'dormitory',
                'default' => 'dormitory',
                'in' => ['dormitory', 'single', 'double', 'triple', 'quad', 'isolation'],
            ],
            'capacity' => [
                'label' => __('Capacity'),
                'required' => false,
                'guesses' => ['Capacity'],
                'example' => '8',
                'default' => '1',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Hostel Name', 'Building Name', 'Floor Number', 'Wing Name',
            'Room Number', 'Room Name', 'Room Type', 'Capacity',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = HostelRoom::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('wing.floor.building.hostel')
            ->orderBy('id');

        $lastId = 0;

        do {
            $rooms = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($rooms->isEmpty()) {
                break;
            }

            foreach ($rooms as $room) {
                $floor = $room->wing?->floor;
                $building = $floor?->building;

                yield [
                    $building?->hostel?->name,
                    $building?->name,
                    $floor?->floor_number,
                    $room->wing?->name,
                    $room->room_number,
                    $room->name,
                    $room->room_type,
                    $room->capacity,
                ];
            }

            $lastId = $rooms->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $hostels = Hostel::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($h): string => strtolower(trim($h->name)));

        $buildings = HostelBuilding::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($b): string => $b->hostel_id.'::'.strtolower(trim($b->name)));

        $floors = HostelFloor::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($f): string => $f->building_id.'::'.trim((string) $f->floor_number));

        $wings = HostelWing::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($w): string => $w->floor_id.'::'.strtolower(trim($w->name)));

        $existingRoomNumbers = HostelRoom::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->mapWithKeys(fn ($r): array => [$r->floor_id.'::'.strtolower(trim($r->room_number)) => true]);

        $lookups = [
            'hostels' => $hostels,
            'buildings' => $buildings,
            'floors' => $floors,
            'wings' => $wings,
            'existingRoomNumbers' => $existingRoomNumbers,
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

        foreach (['hostel', 'building', 'floor_number', 'wing', 'room_number'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
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

        if (isset($data['_floor']) && $data['_floor'] && $data['room_number'] !== '') {
            $roomKey = $data['_floor']->id.'::'.strtolower(trim($data['room_number']));
            if (isset($lookups['existingRoomNumbers'][$roomKey])) {
                $errors[] = 'Room Number ['.$data['room_number'].'] already exists in Floor ['.$data['_floor']->floor_number.'].';
            }
        }

        $data['room_type'] = strtolower(trim($data['room_type'] ?? ''));
        if ($data['room_type'] === '') {
            $data['room_type'] = 'dormitory';
        }
        if (! in_array($data['room_type'], ['dormitory', 'single', 'double', 'triple', 'quad', 'isolation'], true)) {
            $errors[] = 'Room Type must be one of: dormitory, single, double, triple, quad, isolation.';
        }

        $capacity = trim($data['capacity'] ?? '');
        if ($capacity === '') {
            $capacity = '1';
        }
        $data['capacity'] = $capacity;
        if (! is_numeric($capacity)) {
            $errors[] = 'Capacity ['.$capacity.'] must be a number.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        HostelRoom::create([
            'school_id' => $schoolId,
            'hostel_id' => $data['_hostel']->id,
            'wing_id' => $data['_wing']->id,
            'floor_id' => $data['_floor']->id,
            'room_number' => $data['room_number'],
            'name' => $data['name'] !== '' ? $data['name'] : null,
            'room_type' => $data['room_type'],
            'capacity' => (int) $data['capacity'],
        ]);

        $lookups['existingRoomNumbers'][$data['_floor']->id.'::'.strtolower(trim($data['room_number']))] = true;
    }
}
