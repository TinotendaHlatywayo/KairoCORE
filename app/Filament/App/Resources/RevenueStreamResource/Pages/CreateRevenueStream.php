<?php

namespace App\Filament\App\Resources\RevenueStreamResource\Pages;

use App\Filament\App\Resources\RevenueStreamResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Finance\Models\SchoolBankAccount;

class CreateRevenueStream extends CreateRecord
{
    protected static string $resource = RevenueStreamResource::class;

    /**
     * A new revenue stream credits the linked bank account (or the school's
     * default account when none is picked) with its default amount, so the
     * account balance stays in step with the revenue it represents.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        if (! $record || ! $schoolId) {
            return;
        }

        $amount = (float) $record->default_amount;

        if ($amount <= 0 || ! $record->is_active) {
            return;
        }

        $accountId = $record->account_id ?? SchoolBankAccount::where('school_id', $schoolId)
            ->where('is_default', true)
            ->value('id');

        if (! $accountId) {
            return;
        }

        $credited = SchoolBankAccount::where('school_id', $schoolId)
            ->where('id', $accountId)
            ->increment('balance', $amount);

        if ($credited) {
            Notification::make()
                ->title(__('Revenue added to bank account'))
                ->body(__(':amount credited to the account balance.', ['amount' => '$'.number_format($amount, 2)]))
                ->success()
                ->send();
        }
    }
}
