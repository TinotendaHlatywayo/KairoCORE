<?php

namespace App\Filament\Admin\Resources\SaaSPlanResource\Pages;

use App\Filament\Admin\Resources\SaaSPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateSaaSPlan extends CreateRecord
{
    protected static string $resource = SaaSPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['trial_days'] = ($data['trial_days'] ?? '') === '' ? 14 : (int) $data['trial_days'];
        $data['grace_days'] = ($data['grace_days'] ?? '') === '' ? 7 : (int) $data['grace_days'];

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? ''));
        }

        return $data;
    }
}
