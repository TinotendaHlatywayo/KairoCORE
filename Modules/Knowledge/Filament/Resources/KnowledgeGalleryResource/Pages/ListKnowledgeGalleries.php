<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use Filament\Resources\Pages\ListRecords;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource;

class ListKnowledgeGalleries extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = KnowledgeGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
