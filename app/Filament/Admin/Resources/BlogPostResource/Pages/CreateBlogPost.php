<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BlogPost::prepareAttributes($data);
    }
}
