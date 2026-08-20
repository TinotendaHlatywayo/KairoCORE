<?php

namespace App\Filament\App\Resources;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsTemplateService;

class CmsWebsiteResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Website');
    }

    protected static ?string $model = CmsWebsite::class;

    // Sidebar Properties
    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Web Platform Settings';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    /** Website-wide settings are managed from Website Studio, not as a separate sidebar destination. */
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
        // Shadow websites backing site templates are managed from the Website
        // Templates hub, never from this settings resource.
        return parent::getEloquentQuery()->where('is_template_site', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ViewField::make('template_gallery')
                    ->label(__('Choose your website foundation'))
                    ->view('filament.forms.components.cms-template-picker')
                    ->viewData(['templates' => CmsTemplateService::getTemplates()])
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Select::make('active_template')
                    ->options(collect(CmsTemplateService::getTemplates())->mapWithKeys(fn (array $template, string $key) => [$key => $template['name']])->all())
                    ->required()
                    ->hidden(),
                Select::make('font_primary')
                    ->options([
                        'Inter' => __('Inter'),
                        'Space Grotesk' => __('Space Grotesk'),
                        'Quicksand' => __('Quicksand'),
                    ]),
                ColorPicker::make('color_primary'),
                ColorPicker::make('color_secondary'),
                TextInput::make('seo_title_suffix')->label(__('Title Suffix (e.g. | Royal Academy)')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('active_template')->label(__('Active Template'))->searchable(),
                TextColumn::make('font_primary')->label(__('Primary Font')),
                TextColumn::make('color_primary')->label(__('Primary Accent')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCmsWebsites::route('/'),
        ];
    }
}

// Inline Page Class Definition (Matches your other module standards)
class ManageCmsWebsites extends ManageRecords
{
    protected static string $resource = CmsWebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
