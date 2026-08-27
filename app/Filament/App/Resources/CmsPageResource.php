<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Pages\VisualCmsBuilder;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;

class CmsPageResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Website');
    }

    protected static ?string $model = CmsPage::class;

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Manage Website Pages';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function can(string $action, ?Model $record = null): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('website', fn ($query) => $query->where('is_template_site', false));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    ),
                TextInput::make('slug')
                    ->required()
                    // URLs are unique per website (live + each template), not across
                    // the whole Kairo CORE platform. This matches the database layout
                    // after template sites were introduced and lets every tenant have
                    // their own /home, /about and /admissions pages.
                    ->unique(
                        CmsPage::class,
                        'slug',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('cms_website_id', static fn () => CmsWebsite::where('school_id', app('current_tenant')->id ?? 0)->where('is_template_site', false)->value('id') ?? 0),
                    ),
                Checkbox::make('is_published')->label(__('Published')),
                Checkbox::make('is_homepage')->label(__('Set as Homepage')),

                Hidden::make('cms_website_id')
                    ->default(function () {
                        $schoolId = app('current_tenant')->id ?? 1;

                        $website = CmsWebsite::firstOrCreate([
                            'school_id' => $schoolId,
                            'is_template_site' => false,
                        ], [
                            'active_template' => 'heritage-editorial',
                            'font_primary' => 'Inter',
                            'font_secondary' => 'Outfit',
                            'navigation_menu' => [
                                ['label' => 'Home', 'url' => '/'],
                                ['label' => 'Apply Online', 'url' => '/apply-online'],
                            ],
                        ]);

                        return $website->id;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->sortable(),
                IconColumn::make('is_homepage')->boolean()->label(__('Homepage')),
                IconColumn::make('is_published')->boolean()->label(__('Published')),
                TextColumn::make('updated_at')->dateTime()->label(__('Last Modified')),
            ])
            ->actions([
                Action::make('visual_editor')
                    ->label(__('Open Visual Studio'))
                    ->icon('heroicon-o-paint-brush')
                    ->url(fn (CmsPage $record): string => VisualCmsBuilder::getUrl(['pageId' => $record->id]))
                    ->color('success'),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCmsPages::route('/'),
        ];
    }
}

class ManageCmsPages extends ManageRecords
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
