<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\SupplierResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\SupplierResource;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;
}
