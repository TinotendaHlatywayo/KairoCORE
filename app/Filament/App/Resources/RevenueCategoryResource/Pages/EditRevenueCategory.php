<?php

namespace App\Filament\App\Resources\RevenueCategoryResource\Pages;

use App\Filament\App\Resources\RevenueCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevenueCategory extends EditRecord
{
    protected static string $resource = RevenueCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
