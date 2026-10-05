<?php

namespace App\Filament\App\Resources\HostelAttendanceResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\HostelAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostelAttendances extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = HostelAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
