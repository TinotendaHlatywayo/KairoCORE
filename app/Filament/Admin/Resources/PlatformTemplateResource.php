<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformTemplateResource\Pages\ListPlatformTemplates;
use App\Models\User;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource; // Cleanly imported [1]
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table; // Direct page import
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformTemplate;

class PlatformTemplateResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Communication');
    }

    protected static ?string $model = PlatformTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Global Templates';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

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
                TextInput::make('name')->required(),
                Select::make('category')
                    ->options([
                        'website' => __('Website Layout Blueprint'),
                        'report_card' => __('Academic Report Card'),
                        'id_card' => __('PVC Student ID Badge'),
                        'email' => __('SMTP Transactional Email'),
                    ])->required(),
                KeyValue::make('configuration_blueprint')
                    ->label(__('Template Default Settings & Styles Matrix'))
                    ->columnSpanFull(),
                Toggle::make('is_active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformTemplates::route('/'),
        ];
    }
}
