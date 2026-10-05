<?php

namespace App\Filament\App\Resources\SystemAuditLogResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\SystemAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListSystemAuditLogs extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = SystemAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
