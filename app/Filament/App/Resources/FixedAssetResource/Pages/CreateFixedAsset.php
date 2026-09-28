<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\FixedAssetResource\Pages;

use App\Filament\App\Resources\FixedAssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedAsset extends CreateRecord
{
    protected static string $resource = FixedAssetResource::class;

    /**
     * A newly acquired asset is worth what it cost. Only fall back when the
     * user left the current value blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['current_value'] = filled($data['current_value'] ?? null)
            ? $data['current_value']
            : ($data['purchase_cost'] ?? null);

        return $data;
    }
}
