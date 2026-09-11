<?php

namespace App\Filament\App\Resources\HostelAllocationResource\Pages;

use App\Filament\App\Resources\HostelAllocationResource;
use Filament\Resources\Pages\CreateRecord;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelRoom;

class CreateHostelAllocation extends CreateRecord
{
    protected static string $resource = HostelAllocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $roomId = $data['room_id'] ?? null;
        if ($roomId) {
            $room = HostelRoom::find($roomId);
            $activeCount = HostelAllocation::where('room_id', $roomId)->where('status', 'active')->count();
            $capacity = $room?->capacity ?? 1;

            $studentId = $data['student_id'] ?? null;
            $existing = HostelAllocation::where('student_id', $studentId)->where('status', 'active')->first();

            if ($activeCount >= $capacity && (! $existing || $existing->room_id !== $roomId)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'room_id' => 'This room is full (capacity: ' . $capacity . '). Please choose another room or reallocate.',
                ]);
            }
        }

        if (! empty($data['student_id'])) {
            $activeAllocations = HostelAllocation::where('student_id', $data['student_id'])
                ->where('status', 'active')
                ->get();

            foreach ($activeAllocations as $oldAlloc) {
                $oldAlloc->update([
                    'status' => 'completed',
                    'checked_out_at' => now(),
                ]);
            }
        }

        return $data;
    }
}
