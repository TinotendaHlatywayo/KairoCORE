<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource;

class ListKnowledgeGalleries extends ListRecords
{
    protected static string $resource = KnowledgeGalleryResource::class;
}
