<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\AssetMaintenanceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;

class CreateAssetMaintenance extends CreateRecord
{
    protected static string $resource = AssetMaintenanceResource::class;
}
