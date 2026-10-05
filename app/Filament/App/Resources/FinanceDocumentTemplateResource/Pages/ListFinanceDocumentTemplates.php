<?php

namespace App\Filament\App\Resources\FinanceDocumentTemplateResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\FinanceDocumentTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceDocumentTemplates extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = FinanceDocumentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(), Actions\CreateAction::make()];
    }
}
