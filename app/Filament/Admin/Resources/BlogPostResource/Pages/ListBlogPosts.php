<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('New Blog Post'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
