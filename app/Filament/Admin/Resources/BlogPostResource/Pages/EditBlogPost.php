<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return BlogPost::prepareAttributes($data, $this->record->published_at);
    }
}
