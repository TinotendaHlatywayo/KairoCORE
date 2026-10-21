<?php

namespace App\Filament\Admin\Resources\SaaSPlanResource\Pages;

use App\Filament\Admin\Resources\SaaSPlanResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditSaaSPlan extends EditRecord
{
    protected static string $resource = SaaSPlanResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clearable numeric inputs submit null while the columns are NOT NULL
        // (previously: 500 "Column 'trial_days' cannot be null" on Save).
        $data['trial_days'] = ($data['trial_days'] ?? '') === '' ? 14 : (int) $data['trial_days'];
        $data['grace_days'] = ($data['grace_days'] ?? '') === '' ? 7 : (int) $data['grace_days'];

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? ''));
        }

        return $data;
    }
}
