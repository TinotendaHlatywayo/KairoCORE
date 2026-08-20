<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryProcurementResource\Pages;

use App\Models\School;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Inventory\Filament\Resources\InventoryProcurementResource;

class CreateInventoryProcurement extends CreateRecord
{
    protected static string $resource = InventoryProcurementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = app('current_tenant');

        $data['school_id'] = $tenant instanceof School ? $tenant->id : null;
        $data['requested_by_id'] = Auth::id();

        return $data;
    }
}
