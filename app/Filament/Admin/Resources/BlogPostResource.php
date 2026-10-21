<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?int $navigationSort = 3;

    /**
     * Blog management now lives in the Website Content sidebar page's tab
     * strip. The resource stays registered (deep links, forms, actions) but
     * must not add its own "Website → Blog" sidebar entry.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Post'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug((string) $state))),
                        Forms\Components\TextInput::make('slug')
                            ->unique(table: BlogPost::class, ignoreRecord: true)
                            ->helperText(__('Leave blank to generate one from the title.'))
                            ->maxLength(255),
                        Forms\Components\Textarea::make('excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('Short summary shown on the blog index and in search results.'))
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label(__('Cover image'))
                            ->disk('public')
                            ->directory('platform/blog/covers')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->nullable(),
                        Forms\Components\RichEditor::make('content')
                            ->label(__('Content'))
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3',
                                'link',
                                'orderedList', 'bulletList', 'blockquote',
                                'attachFiles',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('platform/blog/attachments')
                            ->fileAttachmentsVisibility('public')
                            ->helperText(__('Select any word or phrase and use the link icon to make it a hyperlink. Use the paperclip to embed images.')),
                    ])->columns(2),

                Forms\Components\Section::make(__('Publishing'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                BlogPost::STATUS_DRAFT => __('Draft'),
                                BlogPost::STATUS_PUBLISHED => __('Published'),
                            ])
                            ->default(BlogPost::STATUS_DRAFT)
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->native(false)
                            ->displayFormat('d M Y H:i')
                            ->helperText(__('Set automatically the first time a draft is published.')),
                        Forms\Components\TextInput::make('seo_description')
                            ->maxLength(300)
                            ->helperText(__('Meta description for search engines.'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->disk('public')
                    ->default('/images/placeholders/news-cover.svg')
                    ->circular(false),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BlogPost $record): string => (string) $record->excerpt),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === BlogPost::STATUS_PUBLISHED ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        BlogPost::STATUS_DRAFT => __('Draft'),
                        BlogPost::STATUS_PUBLISHED => __('Published'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
