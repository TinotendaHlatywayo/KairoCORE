<?php

namespace Modules\Clinic\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Clinic\Models\ClinicPrescription;
use Modules\Clinic\Models\ClinicVisit;
use Modules\Inventory\Services\FifoQueueManager;

class ClinicVisitService
{
    public function discharge(int $schoolId, int $visitId, ?string $treatment = null, ?string $diagnosis = null): void
    {
        DB::transaction(function () use ($schoolId, $visitId, $treatment, $diagnosis) {
            $visit = ClinicVisit::where('school_id', $schoolId)->where('id', $visitId)->lockForUpdate()->firstOrFail();

            $visit->update([
                'status' => 'discharged',
                'departure_time' => now(),
                'treatment_given' => $treatment ?? $visit->treatment_given,
                'diagnosis' => $diagnosis ?? $visit->diagnosis,
            ]);
        });
    }

    public function dispense(int $schoolId, int $prescriptionId, int $quantityToDispense, int $clinicLocationId): void
    {
        DB::transaction(function () use ($schoolId, $prescriptionId, $quantityToDispense, $clinicLocationId) {
            $prescription = ClinicPrescription::where('school_id', $schoolId)
                ->where('id', $prescriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($prescription->quantity_dispensed + $quantityToDispense > $prescription->quantity_prescribed) {
                throw new Exception('The requested quantity exceeds the remaining prescribed dosage.');
            }

            $prescription->increment('quantity_dispensed', $quantityToDispense);

            if ($prescription->quantity_dispensed === $prescription->quantity_prescribed) {
                $prescription->update(['dispensed_at' => now()]);
            }

            // Deduct stock from Phase 8 Inventory module if inventory tracking is linked
            if ($prescription->inventory_item_id && class_exists(FifoQueueManager::class)) {
                $item = $prescription->inventoryItem;
                app(FifoQueueManager::class)->issueStock(
                    $item,
                    $clinicLocationId,
                    $quantityToDispense,
                    ClinicPrescription::class,
                    $prescription->id,
                    Auth::id(),
                    false // Consumable medicine cannot be returned
                );
            }
        });
    }
}
