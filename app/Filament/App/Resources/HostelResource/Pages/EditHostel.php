<?php

namespace App\Filament\App\Resources\HostelResource\Pages;

use App\Filament\App\Resources\HostelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostel extends EditRecord
{
    protected static string $resource = HostelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
