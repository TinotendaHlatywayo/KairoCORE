<?php

namespace App\Filament\App\Resources\FeeWaiverResource\Pages;

use App\Filament\App\Resources\FeeWaiverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeWaivers extends ListRecords
{
    protected static string $resource = FeeWaiverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
