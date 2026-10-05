<?php

namespace App\Filament\App\Resources\FeeWaiverResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\FeeWaiverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeWaivers extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = FeeWaiverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
