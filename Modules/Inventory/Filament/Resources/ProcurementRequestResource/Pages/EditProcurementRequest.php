<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource;

class EditProcurementRequest extends EditRecord
{
    protected static string $resource = ProcurementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
