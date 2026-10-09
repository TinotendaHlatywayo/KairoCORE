<?php

namespace App\Filament\Admin\Resources\PlatformExpenseResource\Pages;

use App\Filament\Admin\Resources\PlatformExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Modules\SaaS\Models\PlatformExpense;

class CreatePlatformExpense extends CreateRecord
{
    protected static string $resource = PlatformExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data = $this->normaliseRecurrence($data);

        return $data;
    }

    protected function normaliseRecurrence(array $data): array
    {
        if (empty($data['is_recurring'])) {
            $data['is_recurring'] = false;
            $data['recurrence'] = null;
            $data['next_due_date'] = null;
            $data['recurrence_ends_at'] = null;

            return $data;
        }

        if (empty($data['next_due_date']) && ! empty($data['recurrence']) && ! empty($data['expense_date'])) {
            $data['next_due_date'] = PlatformExpense::nextOccurrenceFor(
                (string) $data['recurrence'],
                $data['expense_date'],
            )->toDateString();
        }

        return $data;
    }
}
