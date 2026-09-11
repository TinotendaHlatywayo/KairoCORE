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
                    Forms\Components\Wizard\Step::make(__('Student Mapping'))
                        ->schema([
                            Forms\Components\Select::make('student_id')
                                ->label(__('Student'))
                                ->options(function () {
                                    $allocatedStudents = \Modules\Hostels\Models\HostelAllocation::where('status', 'active')
                                        ->with('bed.room.hostel')
                                        ->get()
                                        ->keyBy('student_id');

                                    return \Modules\Students\Models\Student::query()
                                        ->get()
                                        ->mapWithKeys(function ($s) use ($allocatedStudents) {
                                            $name = $s->first_name . ' ' . $s->last_name;
                                            $alloc = $allocatedStudents->get($s->id);
                                            if ($alloc) {
                                                $hostelName = $alloc->bed?->room?->hostel?->name ?? 'Hostel';
                                                $roomNo = $alloc->bed?->room?->room_number ?? 'N/A';
                                                return [$s->id => $name . " [Already Allocated to {$hostelName} - Room {$roomNo}]"];
                                            }
                                            return [$s->id => $name];
                                        });
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    $s = \Modules\Students\Models\Student::find($value);
                                    if (! $s) return null;
                                    $name = $s->first_name . ' ' . $s->last_name;
                                    $alloc = \Modules\Hostels\Models\HostelAllocation::where('student_id', $s->id)->where('status', 'active')->with('bed.room.hostel')->first();
                                    if ($alloc) {
                                        $hostelName = $alloc->bed?->room?->hostel?->name ?? 'Hostel';
                                        $roomNo = $alloc->bed?->room?->room_number ?? 'N/A';
                                        return $name . " [Already Allocated to {$hostelName} - Room {$roomNo}]";
                                    }
                                    return $name;
                                })
                                ->getSearchResultsUsing(fn (string $search): array => \Modules\Students\Models\Student::query()
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('admission_number', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($s) {
                                        $name = $s->first_name . ' ' . $s->last_name . ' (' . $s->admission_number . ')';
                                        $alloc = \Modules\Hostels\Models\HostelAllocation::where('student_id', $s->id)->where('status', 'active')->with('bed.room.hostel')->first();
                                        if ($alloc) {
                                            $hostelName = $alloc->bed?->room?->hostel?->name ?? 'Hostel';
                                            $roomNo = $alloc->bed?->room?->room_number ?? 'N/A';
                                            return [$s->id => $name . " [Already Allocated to {$hostelName} - Room {$roomNo}]"];
                                        }
                                        return [$s->id => $name];
                                    })
                                    ->all())
                                ->searchable()
                                ->reactive()
                                ->required(),
                            Forms\Components\Placeholder::make('student_allocation_warning')
                                ->hidden(fn (Forms\Get $get) => blank($get('student_id')))
                                ->content(function (Forms\Get $get) {
                                    $studentId = $get('student_id');
                                    if (! $studentId) {
                                        return '';
                                    }
                                    $allocation = \Modules\Hostels\Models\HostelAllocation::where('student_id', $studentId)
                                        ->where('status', 'active')
                                        ->with('bed.room.hostel')
                                        ->first();
                                    if ($allocation) {
                                        $room = $allocation->bed?->room;
                                        $hostel = $room?->hostel;
                                        return new \Illuminate\Support\HtmlString('<span style="color: #b91c1c; font-weight: 600;">⚠️ This student is already allocated to ' . ($hostel?->name ?? 'Hostel') . ' — Room ' . ($room?->room_number ?? 'N/A') . '. Submitting will reallocate them to this new room.</span>');
                                    }
                                    return new \Illuminate\Support\HtmlString('<span style="color: #047857;">✓ Student is not currently allocated to any room.</span>');
                                }),
                            Forms\Components\Select::make('academic_year_id')
                                ->relationship('academicYear', 'name')
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make(__('Room Mapping'))
                        ->schema([
                            Forms\Components\Select::make('room_id')
                                ->label(__('Room'))
                                ->options(fn () => HostelRoom::with(['hostel', 'beds.allocations' => fn ($q) => $q->where('status', 'active')])
                                    ->get()
                                    ->mapWithKeys(function ($r) {
                                        $activeCount = \Modules\Hostels\Models\HostelAllocation::where('room_id', $r->id)->where('status', 'active')->count();
                                        $capacity = $r->capacity ?? 1;
                                        $label = ($r->hostel?->name ? $r->hostel->name . ' — ' : '') . 'Room ' . $r->room_number . ' (Occupied: ' . $activeCount . '/' . $capacity . ')';
                                        if ($activeCount >= $capacity) {
                                            $label .= ' [FULL]';
                                        }
                                        return [$r->id => $label];
                                    }))
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn ($set) => $set('bed_id', null)),
                            Forms\Components\Placeholder::make('room_capacity_info')
                                ->hidden(fn (Forms\Get $get) => blank($get('room_id')))
                                ->content(function (Forms\Get $get) {
                                    $roomId = $get('room_id');
                                    if (! $roomId) {
                                        return '';
                                    }
                                    $room = HostelRoom::find($roomId);
                                    if (! $room) {
                                        return '';
                                    }
                                    $activeCount = \Modules\Hostels\Models\HostelAllocation::where('room_id', $roomId)->where('status', 'active')->count();
                                    $capacity = $room->capacity ?? 1;
                                    if ($activeCount >= $capacity) {
                                        return new \Illuminate\Support\HtmlString('<span style="color: #b91c1c; font-weight: 600;">❌ This room is FULL (Capacity: ' . $capacity . ', Occupied: ' . $activeCount . '). You cannot add more students unless you reallocate or update capacity.</span>');
                                    }
                                    return new \Illuminate\Support\HtmlString('<span style="color: #047857;">✓ Room has space (Capacity: ' . $capacity . ', Occupied: ' . $activeCount . ').</span>');
                                }),
                            Forms\Components\Select::make('bed_id')
                                ->label(__('Bed'))
                                ->options(fn ($get) => HostelBed::where('room_id', $get('room_id'))
                                    ->where('status', 'vacant')
                                    ->get()
                                    ->mapWithKeys(fn ($b) => [$b->id => $b->bed_number . ' (' . ucfirst($b->condition) . ')']))
                                ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('bed_number')
                                        ->label(__('Bed Number'))
                                        ->required()
                                        ->maxLength(50)
                                        ->placeholder('e.g., B-1'),
                                    Forms\Components\Select::make('condition')
                                        ->label(__('Condition'))
                                        ->options([
                                            'good' => __('Good'),
                                            'fair' => __('Fair'),
                                            'poor' => __('Poor'),
                                        ])
                                        ->default('good')
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data, $get) {
                                    $data['room_id'] = $get('room_id');
                                    $data['school_id'] = current_tenant()?->id ?? auth()->user()->school_id;
                                    $data['status'] = 'vacant';
                                    $data['cleaning_status'] = 'clean';
                                    return HostelBed::create($data)->id;
                                })
                                ->required(),
                        ]),
                    Forms\Components\Wizard\Step::make(__('Processing Rules'))
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
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label(__('Student'))
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => trim(($record->student->first_name ?? '') . ' ' . ($record->student->last_name ?? ''))),
                Tables\Columns\TextColumn::make('bed.room.hostel.name')
                    ->label(__('Hostel Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('bed.room.wing.name')
                    ->label(__('Wing'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('bed.room.floor.floor_number')
                    ->label(__('Floor'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('bed.room.room_number')->label(__('Room')),
                Tables\Columns\TextColumn::make('bed.bed_number')->label(__('Bed')),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->recordUrl(fn (HostelAllocation $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelAllocations::route('/'),
            'create' => Pages\CreateHostelAllocation::route('/create'),
            'view' => Pages\ViewHostelAllocation::route('/{record}'),
            'edit' => Pages\EditHostelAllocation::route('/{record}/edit'),
        ];
    }
}
