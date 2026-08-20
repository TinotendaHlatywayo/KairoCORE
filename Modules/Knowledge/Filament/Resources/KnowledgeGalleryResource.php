<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Knowledge\Filament\Resources\KnowledgeGalleryResource\Pages\ListKnowledgeGalleries;
use Modules\Knowledge\Models\KnowledgeAsset;
use ZipArchive;

class KnowledgeGalleryResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Hub');
    }

    protected static ?string $model = KnowledgeAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Repository Gallery';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Knowledge Hub';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('media_type', '!=', 'physical'))
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('cover_image_path')
                        ->label(__('Cover'))
                        ->square()
                        ->height(180)
                        ->width(240)
                        // Corrected fallback path
                        ->defaultImageUrl(asset('images/School_repository_cover.jpeg')),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('title')
                            ->weight('bold')
                            ->searchable()
                            ->sortable(),

                        Tables\Columns\TextColumn::make('authors.name')
                            ->label(__('Authors'))
                            ->color('gray')
                            ->size('sm')
                            ->searchable(),

                        Tables\Columns\TextColumn::make('category.name')
                            ->label(__('Category'))
                            ->color('gray')
                            ->size('xs')
                            ->searchable(),

                        Tables\Columns\TextColumn::make('format.name')
                            ->label(__('Format Class'))
                            ->color('primary')
                            ->size('sm')
                            ->searchable(),

                        Tables\Columns\TextColumn::make('media_type')
                            ->label(__('Classification'))
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->badge()
                            ->color('success'),
                    ])->space(1),
                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('knowledge_format_id')
                    ->label(__('Format Class'))
                    ->relationship('format', 'name'),

                Tables\Filters\SelectFilter::make('library_category_id')
                    ->label(__('Subject Category'))
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('authors')
                    ->label(__('Author / Creator'))
                    ->relationship('authors', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_digital')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => ! empty($record->file_path))
                    ->url(fn ($record) => route('knowledge-asset.view', $record->id), shouldOpenInNewTab: true),

                Tables\Actions\Action::make('download')
                    ->label(__('Download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ! empty($record->file_path))
                    ->action(function ($record) {
                        $disk = Storage::disk('public');
                        if (! $disk->exists($record->file_path)) {
                            abort(404, 'Requested document file does not exist on disk.');
                        }

                        return response()->download($disk->path($record->file_path));
                    }),

                Tables\Actions\Action::make('view_link')
                    ->label(__('Open Link'))
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(fn ($record) => ! empty($record->external_url))
                    ->url(fn ($record) => $record->external_url, shouldOpenInNewTab: true),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_download')
                    ->label(__('Pack & Download Selected'))
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $zip = new ZipArchive;
                        $zipName = 'Repository-Pack-'.time().'.zip';
                        $zipPath = storage_path('app/public/'.$zipName);

                        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                            $disk = Storage::disk('public');
                            foreach ($records as $record) {
                                if (! empty($record->file_path) && $disk->exists($record->file_path)) {
                                    $physicalPath = $disk->path($record->file_path);
                                    $zip->addFile($physicalPath, basename($physicalPath));
                                }
                            }
                            $zip->close();
                        }

                        return response()->download($zipPath)->deleteFileAfterSend(true);
                    }),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeGalleries::route('/'),
        ];
    }
}
