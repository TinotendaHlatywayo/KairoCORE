<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Models\School;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Library\Filament\Resources\LibraryBookResource\Pages\CreateLibraryBook;
use Modules\Library\Filament\Resources\LibraryBookResource\Pages\EditLibraryBook;
use Modules\Library\Filament\Resources\LibraryBookResource\Pages\ListLibraryBooks;
use Modules\Library\Filament\Resources\LibraryBookResource\RelationManagers\CopiesRelationManager;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryFormat;

class LibraryBookResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Library');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = LibraryBook::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Books & Resources';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Library';

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('library')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('library.view_module');
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make('Resource Specification')
                            ->description(__('Input bibliographic records, catalog files, and digital links.'))
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label(__('Resource Title'))
                                    ->placeholder(__('e.g., Advanced Physical Chemistry'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('subtitle')
                                    ->label(__('Subtitle'))
                                    ->placeholder(__('e.g., Year 12 Curriculum Study Guide'))
                                    ->maxLength(255),

                                Forms\Components\Select::make('authors')
                                    ->label(__('Author(s) / Creator(s)'))
                                    ->hint(__('Select multiple authors or click "+" to add a new Author'))
                                    ->placeholder(__('Type to search or click "+" to add a new Author'))
                                    ->relationship('authors', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Author Name'))
                                            ->placeholder(__('e.g., Dr. Arthur C. Clarke'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                table: 'library_authors',
                                                column: 'name',
                                                modifyRuleUsing: function ($rule) {
                                                    $tenant = app('current_tenant');
                                                    $tenantId = $tenant instanceof School ? $tenant->id : null;

                                                    return $rule->where('school_id', $tenantId);
                                                }
                                            ),
                                        Forms\Components\Textarea::make('bio')
                                            ->placeholder(__('Brief background history...')),
                                    ]),

                                Forms\Components\Select::make('library_category_id')
                                    ->label(__('System Category'))
                                    ->hint(__('Categorizes the resource under a subject classification (e.g., Mathematics).'))
                                    ->placeholder(__('Type to search or click "+" to add a Category'))
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Category Label'))
                                            ->placeholder(__('e.g., Chemistry Form 3'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                table: 'library_categories',
                                                column: 'name',
                                                modifyRuleUsing: function ($rule) {
                                                    $tenant = app('current_tenant');
                                                    $tenantId = $tenant instanceof School ? $tenant->id : null;

                                                    return $rule->where('school_id', $tenantId);
                                                }
                                            ),
                                    ]),

                                Forms\Components\TextInput::make('isbn')
                                    ->label(__('ISBN Identification'))
                                    ->placeholder(__('e.g., 978-3-16-148410-0'))
                                    ->maxLength(255),
                            ])->columnSpan(2),

                        Forms\Components\Section::make('Storage & Format Parameters')
                            ->schema([
                                Forms\Components\Select::make('media_type')
                                    ->label(__('Classification'))
                                    ->options([
                                        'physical' => __('Physical Resource'),
                                        'digital' => __('Digital eResource'),
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('library_format_id', null);
                                    }),

                                Forms\Components\Select::make('library_format_id')
                                    ->label(__('Format Class'))
                                    ->hint(__('Define custom types (e.g. Green Book) using the "+" button'))
                                    ->placeholder(__('Choose or create a Format Class...'))
                                    ->options(function (Forms\Get $get) {
                                        $mediaType = $get('media_type');
                                        if (! $mediaType) {
                                            return LibraryFormat::all()->pluck('name', 'id');
                                        }

                                        return LibraryFormat::where('media_type', $mediaType)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Custom Format Class Name'))
                                            ->placeholder(__('e.g., Green Book, Revision Paper'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                table: 'library_formats',
                                                column: 'name',
                                                modifyRuleUsing: function ($rule) {
                                                    $tenant = app('current_tenant');
                                                    $tenantId = $tenant instanceof School ? $tenant->id : null;

                                                    return $rule->where('school_id', $tenantId);
                                                }
                                            ),
                                        Forms\Components\Select::make('media_type')
                                            ->label(__('Target Classification'))
                                            ->options([
                                                'physical' => __('Physical Copy Resource'),
                                                'digital' => __('Digital eBook Resource'),
                                            ])
                                            ->required(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $tenant = app('current_tenant');
                                        $data['school_id'] = $tenant instanceof School ? $tenant->id : null;
                                        $record = LibraryFormat::create($data);

                                        return $record->id;
                                    }),

                                Forms\Components\TextInput::make('add_copies_quantity')
                                    ->label(__('Add Physical Copies (Initial Quantity)'))
                                    ->placeholder(__('e.g., 10'))
                                    ->hint(__('Automatically registers this quantity of copies with sequential barcodes'))
                                    ->integer()
                                    ->default(0)
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'physical'),

                                Forms\Components\FileUpload::make('file_path')
                                    ->label(__('Upload e-Resource Document (Supports up to 300MB)'))
                                    ->directory('library/documents')
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'digital')
                                    ->maxSize(307200) // 300MB Max
                                    ->acceptedFileTypes(['application/pdf', 'application/epub+zip', 'application/zip', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),

                                Forms\Components\TextInput::make('external_url')
                                    ->label(__('Resource URL / YouTube Link'))
                                    ->placeholder(__('e.g., https://youtube.com/watch?v=...'))
                                    ->url()
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'digital'),

                                Forms\Components\TextInput::make('publisher')
                                    ->placeholder(__('e.g., Cambridge University Press'))
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('publication_year')
                                    ->placeholder(__('e.g., 2024'))
                                    ->length(4),

                                Forms\Components\FileUpload::make('cover_image_path')
                                    ->label(__('Cover Graphic'))
                                    ->image()
                                    ->directory('library/covers'),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_path')
                    ->label(__('Cover'))
                    ->square()
                    // Set custom local webp asset as default fallback
                    ->defaultImageUrl(asset('images/book-reading-in-library-icon-svg-download-png-1399548.webp')),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('authors.name')
                    ->label(__('Authors'))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('media_type')
                    ->label(__('Classification'))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'physical' => 'info',
                        'digital' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('format.name')
                    ->label(__('Format Class'))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                // RESOURCE QUANTITY COUNTERS
                Tables\Columns\TextColumn::make('copies_count')
                    ->label(__('Total Copies'))
                    ->getStateUsing(fn ($record) => $record->getTotalCopiesCount()),

                Tables\Columns\TextColumn::make('available_copies_count')
                    ->label(__('Available'))
                    ->color('success')
                    ->getStateUsing(fn ($record) => $record->getAvailableCopiesCount()),

                Tables\Columns\TextColumn::make('damaged_copies_count')
                    ->label(__('Damaged'))
                    ->color('warning')
                    ->getStateUsing(fn ($record) => $record->getDamagedCopiesCount()),

                Tables\Columns\TextColumn::make('lost_copies_count')
                    ->label(__('Lost'))
                    ->color('danger')
                    ->getStateUsing(fn ($record) => $record->getLostCopiesCount()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('library_category_id')
                    ->label(__('System Category'))
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('library_format_id')
                    ->label(__('Format Class'))
                    ->relationship('format', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_digital')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn ($record) => $record->media_type === 'digital' && ! empty($record->file_path))
                    ->url(fn ($record) => route('e-resource.view', $record->id), shouldOpenInNewTab: true),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CopiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLibraryBooks::route('/'),
            'create' => CreateLibraryBook::route('/create'),
            'edit' => EditLibraryBook::route('/{record}/edit'),
        ];
    }
}
