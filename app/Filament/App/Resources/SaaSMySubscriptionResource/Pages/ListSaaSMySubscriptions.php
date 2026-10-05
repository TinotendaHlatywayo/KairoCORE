<?php

namespace App\Filament\App\Resources\SaaSMySubscriptionResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\SaaSMySubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSaaSMySubscriptions extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = SaaSMySubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
