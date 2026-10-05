<?php

namespace App\Filament\App\Resources\HostelInspectionResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\HostelInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelInspections extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = HostelInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
