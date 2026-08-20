<?php

namespace App\Filament\App\Resources\HostelRoomResource\Pages;

use App\Filament\App\Resources\HostelRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostelRoom extends EditRecord
{
    protected static string $resource = HostelRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
