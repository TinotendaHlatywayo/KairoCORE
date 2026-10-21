<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? ''));
        }
        if (($data['status'] ?? null) === BlogPost::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = $this->record->published_at ?? now();
        }
        $data['content'] = BlogPost::resolveAttachments((string) ($data['content'] ?? ''));

        return $data;
    }
}
