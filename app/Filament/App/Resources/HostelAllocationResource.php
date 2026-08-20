<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelAllocationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;
use Modules\Hostels\Models\HostelRoom;

class HostelAllocationResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Student Mapping')
                        ->schema([
                            Forms\Components\Select::make('student_id')
                                ->relationship('student', 'first_name')
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('academic_year_id')
                                ->relationship('academicYear', 'name')
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make('Room Mapping')
                        ->schema([
                            Forms\Components\Select::make('room_id')
                                ->options(HostelRoom::pluck('room_number', 'id'))
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn ($set) => $set('bed_id', null)),
                            Forms\Components\Select::make('bed_id')
                                ->options(fn ($get) => HostelBed::where('room_id', $get('room_id'))
                                    ->where('status', 'vacant')
                                    ->pluck('bed_number', 'id')
                                )
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make('Processing Rules')
                        ->schema([
                            Forms\Components\DatePicker::make('allocated_at')
                                ->default(now())
                                ->required(),
                            Forms\Components\DatePicker::make('expected_checkout_at'),
                            Forms\Components\Textarea::make('notes')->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')->label(__('First Name'))->searchable(),
                Tables\Columns\TextColumn::make('student.last_name')->label(__('Last Name'))->searchable(),
                Tables\Columns\TextColumn::make('bed.room.room_number')->label(__('Room')),
                Tables\Columns\TextColumn::make('bed.bed_number')->label(__('Bed')),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelAllocations::route('/'),
            'create' => Pages\CreateHostelAllocation::route('/create'),
            'edit' => Pages\EditHostelAllocation::route('/{record}/edit'),
        ];
    }
}
