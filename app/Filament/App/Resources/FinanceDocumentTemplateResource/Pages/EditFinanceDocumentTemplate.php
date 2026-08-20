<?php

namespace App\Filament\App\Resources\FinanceDocumentTemplateResource\Pages;

use App\Filament\App\Resources\FinanceDocumentTemplateResource;
use Filament\Resources\Pages\EditRecord;

class EditFinanceDocumentTemplate extends EditRecord
{
    protected static string $resource = FinanceDocumentTemplateResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return FinanceDocumentTemplateResource::fillLayoutConfigDefaults($data);
    }
}
