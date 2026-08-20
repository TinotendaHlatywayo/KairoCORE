<?php

namespace Modules\Hostels\Services;

use Exception;
use Modules\Hostels\Models\HostelOutPass;

class OutPassService
{
    public function verifyOtp(int $schoolId, int $outPassId, string $otp): bool
    {
        $outPass = HostelOutPass::where('school_id', $schoolId)->where('id', $outPassId)->firstOrFail();

        if ($outPass->parent_otp === $otp) {
            $outPass->update([
                'status' => 'pending_warden',
                'parent_approved_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public function scanGatePass(int $schoolId, string $qrCode, int $scannerUserId): HostelOutPass
    {
        $outPass = HostelOutPass::where('school_id', $schoolId)
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        if ($outPass->status === 'approved') {
            $outPass->update([
                'status' => 'checked_out',
                'actual_departure' => now(),
                'gate_scanned_at' => now(),
                'gate_scanner_id' => $scannerUserId,
            ]);
        } elseif ($outPass->status === 'checked_out') {
            $outPass->update([
                'status' => 'returned',
                'actual_return' => now(),
            ]);
        } else {
            throw new Exception('Invalid gate pass status sequence.');
        }

        return $outPass;
    }
}
