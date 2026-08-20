<?php

namespace App\Filament\App\Resources\FinanceDocumentTemplateResource\Pages;

use App\Filament\App\Resources\FinanceDocumentTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceDocumentTemplates extends ListRecords
{
    protected static string $resource = FinanceDocumentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
