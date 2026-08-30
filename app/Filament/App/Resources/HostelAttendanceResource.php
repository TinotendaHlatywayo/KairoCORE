<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelAttendanceResource\Pages;
use App\Filament\App\Resources\HostelAttendanceResource\RelationManagers\StudentsRelationManager;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelAttendance;

class HostelAttendanceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('hostel_id')
                            ->relationship('hostel', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $set('learners', static::learnersForHostel($state))),
                        Forms\Components\DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'morning' => __('Morning Roll Call'),
                                'evening' => __('Evening Roll Call'),
                                'curfew' => __('Curfew Verification'),
                            ])
                            ->required(),
                        Forms\Components\Hidden::make('recorded_by_user_id')
                            ->default(fn () => Auth::id()),
                    ])->columns(3),

                Forms\Components\Section::make(__('Roll Call'))
                    ->description(__('Tick Present for learners who are in. Untick to mark them Absent.'))
                    ->schema([
                        Forms\Components\Actions::make([
                            Action::make('loadLearners')
                                ->label(__('Load learners from selected hostel'))
                                ->icon('heroicon-o-arrow-path')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $set('learners', static::learnersForHostel($get('hostel_id')));
                                }),
                        ])->columnSpanFull(),

                        Repeater::make('learners')
                            ->label(__('Learners'))
                            ->schema([
                                Forms\Components\Hidden::make('student_id'),
                                TextInput::make('name')
                                    ->label(__('Name & Surname'))
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('hostel')
                                    ->label(__('Hostel'))
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('building')
                                    ->label(__('Building Name'))
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('room')
                                    ->label(__('Room Number'))
                                    ->disabled()
                                    ->dehydrated(),
                                Checkbox::make('is_present')
                                    ->label(__('Present'))
                                    ->default(true)
                                    ->inline(false),
                                Textarea::make('remarks')
                                    ->label(__('Remarks'))
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->live()
                            ->columnSpanFull(),
                    ])
                    ->visibleOn('create')
                    ->columnSpanFull(),
            ]);
    }

    protected static function learnersForHostel(?int $hostelId): array
    {
        if (! $hostelId) {
            return [];
        }

        $hostelName = Hostel::query()->whereKey($hostelId)->value('name');

        return HostelAllocation::query()
            ->where('status', 'active')
            ->whereHas('room', fn ($q) => $q->where('hostel_id', $hostelId))
            ->with(['student', 'room.floor.building'])
            ->get()
            ->map(fn ($allocation) => [
                'student_id' => $allocation->student_id,
                'name' => trim(($allocation->student->first_name ?? '').' '.($allocation->student->last_name ?? '')),
                'hostel' => $hostelName ?? '',
                'building' => $allocation->room?->floor?->building?->name ?? '',
                'room' => $allocation->room?->room_number ?? '',
                'is_present' => true,
                'remarks' => '',
            ])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hostel.name')->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelAttendances::route('/'),
            'create' => Pages\CreateHostelAttendance::route('/create'),
            'edit' => Pages\EditHostelAttendance::route('/{record}/edit'),
        ];
    }
}
