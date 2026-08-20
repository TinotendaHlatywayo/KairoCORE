<?php

namespace Modules\Hostels\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;

class HostelAllocationService
{
    public function allocate(int $schoolId, int $studentId, int $bedId, int $academicYearId, string $allocatedAt, ?string $expectedCheckoutAt = null): HostelAllocation
    {
        return DB::transaction(function () use ($schoolId, $studentId, $bedId, $academicYearId, $allocatedAt, $expectedCheckoutAt) {
            $bed = HostelBed::where('school_id', $schoolId)->where('id', $bedId)->lockForUpdate()->firstOrFail();

            if ($bed->status !== 'vacant') {
                throw new Exception('Selected bed space is currently unavailable.');
            }

            // End active allocations for this student inside the current academic year
            HostelAllocation::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'checked_out_at' => now()->toDateString(),
                ]);

            $allocation = HostelAllocation::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'bed_id' => $bedId,
                'academic_year_id' => $academicYearId,
                'status' => 'active',
                'allocated_at' => $allocatedAt,
                'expected_checkout_at' => $expectedCheckoutAt,
            ]);

            $bed->update(['status' => 'occupied']);

            return $allocation;
        });
    }

    public function swap(int $schoolId, int $allocationIdA, int $allocationIdB): void
    {
        DB::transaction(function () use ($schoolId, $allocationIdA, $allocationIdB) {
            $allocA = HostelAllocation::where('school_id', $schoolId)->where('id', $allocationIdA)->lockForUpdate()->firstOrFail();
            $allocB = HostelAllocation::where('school_id', $schoolId)->where('id', $allocationIdB)->lockForUpdate()->firstOrFail();

            $bedA = $allocA->bed_id;
            $bedB = $allocB->bed_id;

            $allocA->update(['bed_id' => $bedB]);
            $allocB->update(['bed_id' => $bedA]);
        });
    }
}
