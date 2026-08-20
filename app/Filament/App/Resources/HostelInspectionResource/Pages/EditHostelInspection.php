<?php

namespace App\Filament\App\Resources\HostelInspectionResource\Pages;

use App\Filament\App\Resources\HostelInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostelInspection extends EditRecord
{
    protected static string $resource = HostelInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
