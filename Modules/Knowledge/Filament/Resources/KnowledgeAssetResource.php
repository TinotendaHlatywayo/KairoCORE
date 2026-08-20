<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages\CreateKnowledgeAsset;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages\EditKnowledgeAsset;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages\ListKnowledgeAssets;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\RelationManagers\KnowledgeAssetCopiesRelationManager;
use Modules\Knowledge\Models\KnowledgeAsset;
use Modules\Knowledge\Models\KnowledgeFormat;

class KnowledgeAssetResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Hub');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = KnowledgeAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'School Repository';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Knowledge Hub';

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
                                    ->placeholder(__('e.g., Annual Research Journal'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('subtitle')
                                    ->label(__('Subtitle / Volume'))
                                    ->placeholder(__('e.g., Volume 4, Issue 1'))
                                    ->maxLength(255),

                                Forms\Components\Select::make('authors')
                                    ->label(__('Author(s) / Creator(s)'))
                                    ->hint(__('Select multiple contributors or click "+" to add a new Author'))
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
                                    ->label(__('Reference Number / ISBN'))
                                    ->placeholder(__('e.g., REF-978-3-16-148410-0'))
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
                                        $set('knowledge_format_id', null);
                                    }),

                                Forms\Components\Select::make('knowledge_format_id')
                                    ->label(__('Format Class'))
                                    ->hint(__('Define custom types (e.g. Past Paper) using the "+" button'))
                                    ->placeholder(__('Choose or create a Format Class...'))
                                    ->options(function (Forms\Get $get) {
                                        $mediaType = $get('media_type');
                                        if (! $mediaType) {
                                            return KnowledgeFormat::all()->pluck('name', 'id');
                                        }

                                        return KnowledgeFormat::where('media_type', $mediaType)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Custom Format Class Name'))
                                            ->placeholder(__('e.g., Past Paper, Newsletter'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                table: 'knowledge_formats',
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
                                        $record = KnowledgeFormat::create($data);

                                        return $record->id;
                                    }),

                                Forms\Components\TextInput::make('add_copies_quantity')
                                    ->label(__('Add Physical Copies (Initial Quantity)'))
                                    ->placeholder(__('e.g., 10'))
                                    ->hint(__('Automatically registers this quantity of copies with sequential barcodes'))
                                    ->integer()
                                    ->default(0)
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'physical'),

                                Forms\Components\TextInput::make('subtype')
                                    ->label(__('Sub-Classification'))
                                    ->placeholder(__('e.g., term_1_report, science_syllabus, policy_document')),

                                Forms\Components\Select::make('visibility')
                                    ->label(__('Access Visibility'))
                                    ->options([
                                        'private' => __('Private (Archival Staff Only)'),
                                        'library_only' => __('Local Library Terminals Only'),
                                        'teachers_only' => __('Academic Teaching Staff Only'),
                                        'students_only' => __('Student Portal Viewable'),
                                        'public' => __('External Public Website Portal'),
                                        'everyone' => __('Everyone (Global Shared Access)'),
                                    ])
                                    ->required()
                                    ->default('library_only'),

                                Forms\Components\FileUpload::make('file_path')
                                    ->label(__('Upload Document (Supports up to 300MB)'))
                                    ->directory('knowledge/repository')
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'digital')
                                    ->maxSize(307200) // 300MB Max
                                    ->acceptedFileTypes(['application/pdf', 'application/epub+zip', 'application/zip', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),

                                Forms\Components\TextInput::make('external_url')
                                    ->label(__('Resource URL / YouTube Link'))
                                    ->placeholder(__('e.g., https://youtube.com/watch?v=...'))
                                    ->url()
                                    ->visible(fn (Forms\Get $get) => $get('media_type') === 'digital'),

                                Forms\Components\FileUpload::make('cover_image_path')
                                    ->label(__('Cover Graphic'))
                                    ->image()
                                    ->directory('knowledge/covers'),

                                Forms\Components\Textarea::make('abstract_description')
                                    ->label(__('Resource Description / Abstract'))
                                    ->placeholder(__('Enter a summary explaining the contents of this resource file...'))
                                    ->columnSpanFull(),
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
                    // Corrected fallback path
                    ->defaultImageUrl(asset('images/School_repository_cover.jpeg')),

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

                Tables\Columns\TextColumn::make('total_copies')
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

                Tables\Filters\SelectFilter::make('knowledge_format_id')
                    ->label(__('Format Class'))
                    ->relationship('format', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_digital')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn ($record) => $record->media_type === 'digital' && ! empty($record->file_path))
                    ->url(fn ($record) => route('knowledge-asset.view', $record->id), shouldOpenInNewTab: true),

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
            KnowledgeAssetCopiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeAssets::route('/'),
            'create' => CreateKnowledgeAsset::route('/create'),
            'edit' => EditKnowledgeAsset::route('/{record}/edit'),
        ];
    }
}
