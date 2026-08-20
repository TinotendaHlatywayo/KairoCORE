<?php

namespace App\Filament\App\Resources\RevenueStreamResource\Pages;

use App\Filament\App\Resources\RevenueStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevenueStream extends EditRecord
{
    protected static string $resource = RevenueStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
