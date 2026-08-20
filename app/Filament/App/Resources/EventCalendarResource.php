<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Communication\Models\EventCalendar;

class EventCalendarResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = EventCalendar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Event Calendar';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the Communication Center contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Scope')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'academic' => __('Academic Event'),
                                'meetings' => __('Board/Staff Meeting'),
                                'sports' => __('Sports Event'),
                                'examinations' => __('Examination Period'),
                                'holiday' => __('Public Holiday'),
                            ])->required(),
                        Forms\Components\TextInput::make('location'),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Date Boundaries')
                        ->schema([
                            Forms\Components\DateTimePicker::make('start_time')->required(),
                            Forms\Components\DateTimePicker::make('end_time')->required(),
                            Forms\Components\ColorPicker::make('color')
                                ->default('#1e3a8a')
                                ->required(),
                            Forms\Components\Select::make('target_roles')
                                ->multiple()
                                ->options([
                                    'admin' => __('Administrators'),
                                    'teacher' => __('Teachers'),
                                    'student' => __('Students'),
                                    'parent' => __('Parents'),
                                ])->preload(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('start_time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->dateTime(),
                Tables\Columns\TextColumn::make('location'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventCalendars::route('/'),
            'create' => CreateEventCalendar::route('/create'),
            'edit' => EditEventCalendar::route('/{record}/edit'),
        ];
    }
}

class ListEventCalendars extends ListRecords
{
    protected static string $resource = EventCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Event')),
        ];
    }
}
class CreateEventCalendar extends CreateRecord
{
    protected static string $resource = EventCalendarResource::class;
}
class EditEventCalendar extends EditRecord
{
    protected static string $resource = EventCalendarResource::class;
}
