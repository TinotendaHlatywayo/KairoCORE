<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;

class CreateProcurementRequest extends CreateRecord
{
    protected static string $resource = ProcurementRequestResource::class;
}
