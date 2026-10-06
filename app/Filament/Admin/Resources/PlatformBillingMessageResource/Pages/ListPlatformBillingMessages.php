<?php

namespace App\Filament\Admin\Resources\PlatformBillingMessageResource\Pages;

use App\Filament\Admin\Resources\PlatformBillingMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformBillingMessages extends ListRecords
{
    protected static string $resource = PlatformBillingMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
