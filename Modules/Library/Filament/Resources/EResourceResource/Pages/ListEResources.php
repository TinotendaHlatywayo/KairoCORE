<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\EResourceResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\EResourceResource;

class ListEResources extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = EResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
