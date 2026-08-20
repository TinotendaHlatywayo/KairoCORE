<?php

namespace App\Filament\App\Resources\ClinicVisitResource\Pages;

use App\Filament\App\Resources\ClinicVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClinicVisits extends ListRecords
{
    protected static string $resource = ClinicVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
