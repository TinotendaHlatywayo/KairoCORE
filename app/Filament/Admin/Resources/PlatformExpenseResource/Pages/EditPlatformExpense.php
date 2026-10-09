<?php

namespace App\Filament\Admin\Resources\PlatformExpenseResource\Pages;

use App\Filament\Admin\Resources\PlatformExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\SaaS\Models\PlatformExpense;

class EditPlatformExpense extends EditRecord
{
    protected static string $resource = PlatformExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
