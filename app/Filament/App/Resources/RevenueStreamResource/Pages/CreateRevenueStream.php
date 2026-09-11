<?php

namespace App\Filament\App\Resources\RevenueStreamResource\Pages;

use App\Filament\App\Resources\RevenueStreamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRevenueStream extends CreateRecord
{
    protected static string $resource = RevenueStreamResource::class;

    protected function afterCreate(): void
    {
        $stream = $this->record;
        if ($stream && $stream->default_amount > 0) {
            $schoolId = $stream->school_id;
            $bankAccount = \Modules\Finance\Models\SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->first()
                ?? \Modules\Finance\Models\SchoolBankAccount::where('school_id', $schoolId)->first();

            if (! $bankAccount) {
                $bankAccount = \Modules\Finance\Models\SchoolBankAccount::create([
                    'school_id' => $schoolId,
                    'bank_name' => 'Stanbic Bank Zimbabwe',
                    'account_name' => 'School Operating Account',
                    'account_number' => '9140001234567',
                    'branch_code' => '02',
                    'balance' => 0.00,
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }

            \Modules\Finance\Models\Payment::create([
                'school_id' => $schoolId,
                'bank_account_id' => $bankAccount->id,
                'amount' => $stream->default_amount,
                'currency' => 'USD',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'REV-STREAM-' . $stream->id . '-' . rand(1000, 9999),
                'receipt_number' => 'RCP-REV-' . rand(100000, 999999),
                'payment_date' => now()->toDateString(),
            ]);

            $bankAccount->increment('balance', $stream->default_amount);
        }
    }
}
