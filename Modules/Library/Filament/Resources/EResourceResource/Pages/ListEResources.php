<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\EResourceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\EResourceResource;

class ListEResources extends ListRecords
{
    protected static string $resource = EResourceResource::class;
}
