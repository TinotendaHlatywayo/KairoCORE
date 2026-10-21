<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? ''));
        }
        if (($data['status'] ?? null) === BlogPost::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        $data['content'] = BlogPost::resolveAttachments((string) ($data['content'] ?? ''));

        return $data;
    }
}
