<?php

namespace App\Filament\App\Resources\HostelOutPassResource\Pages;

use App\Filament\App\Resources\HostelOutPassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelOutPasses extends ListRecords
{
    protected static string $resource = HostelOutPassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
