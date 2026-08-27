<?php

namespace App\Filament\App\Resources;

use App\Events\HostelOutPassRequested;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelOutPassResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Hostels\Models\HostelOutPass;
use Modules\Hostels\Services\OutPassService;

class HostelOutPassResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelOutPass::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label(__('Student'))
                            ->options(fn () => \Modules\Students\Models\Student::with('currentEnrollment.course')
                                ->where('school_id', current_tenant()?->id)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => $s->first_name . ' ' . $s->last_name
                                        . ' (' . ($s->student_id_number ?? $s->admission_number) . ')',
                                ]))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (! $state) {
                                    $set('hostel_id', null);
                                    return;
                                }
                                $student = \Modules\Students\Models\Student::with([
                                    'currentEnrollment.course',
                                    'currentEnrollment.section',
                                ])->find($state);

                                if (! $student) {
                                    $set('hostel_id', null);
                                    return;
                                }

                                $allocation = \Modules\Hostels\Models\HostelAllocation::with('bed.room.hostel')
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->first();

                                if ($allocation && $allocation->bed && $allocation->bed->room && $allocation->bed->room->hostel) {
                                    $set('hostel_id', $allocation->bed->room->hostel_id);
                                } else {
                                    $set('hostel_id', null);
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make('hostel_id')
                            ->label(__('Hostel'))
                            ->relationship('hostel', 'name')
                            ->required(),

                        Forms\Components\Placeholder::make('student_details_display')
                            ->label(__('Student Details'))
                            ->content(function ($get) {
                                $studentId = $get('student_id');
                                if (! $studentId) {
                                    return __('Select a student to view details.');
                                }

                                $student = \Modules\Students\Models\Student::with([
                                    'currentEnrollment.course',
                                    'currentEnrollment.section',
                                ])->find($studentId);

                                if (! $student) {
                                    return __('Student not found.');
                                }

                                $lines = [];
                                $lines[] = __('Name') . ': <strong>' . e($student->first_name . ' ' . $student->last_name) . '</strong>';
                                $lines[] = __('Student ID') . ': ' . e($student->student_id_number ?? $student->admission_number);
                                $lines[] = __('Gender') . ': ' . e(ucfirst($student->gender));
                                $lines[] = __('Boarding Status') . ': ' . e(ucfirst(str_replace('_', ' ', $student->boarding_status)));

                                $enrollment = $student->currentEnrollment;
                                if ($enrollment) {
                                    $courseName = $enrollment->course?->name ?? '—';
                                    $sectionName = $enrollment->section?->name ?? '—';
                                    $lines[] = __('Grade / Class') . ': ' . e($courseName . ' — ' . $sectionName);
                                }

                                $allocation = \Modules\Hostels\Models\HostelAllocation::with('bed.room.hostel')
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->first();

                                if ($allocation && $allocation->bed) {
                                    $bed = $allocation->bed;
                                    $lines[] = __('Hostel') . ': ' . e($bed->room->hostel?->name ?? '—');
                                    $lines[] = __('Room') . ': ' . e($bed->room->room_number ?? '—');
                                    $lines[] = __('Bed') . ': ' . e($bed->bed_number);
                                } else {
                                    $lines[] = '<span class="text-warning-600">' . __('No active hostel allocation found.') . '</span>';
                                }

                                return new \Illuminate\Support\HtmlString(implode('<br>', $lines));
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Hidden::make('requester_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Select::make('type')
                            ->options([
                                'emergency' => __('Emergency Out-Pass'),
                                'weekend' => __('Weekend Leave'),
                                'medical' => __('Medical Check'),
                                'home' => __('Home Visit'),
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('expected_departure')->required(),
                        Forms\Components\DateTimePicker::make('expected_return')->required(),
                        Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label(__('Student'))
                    ->formatStateUsing(fn ($record) => $record->student
                        ? $record->student->first_name . ' ' . $record->student->last_name
                        : '—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('expected_departure')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('dispatch_otp')
                    ->label(__('Request Parental Approval'))
                    ->color('warning')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->update(['status' => 'pending_parent_otp']);
                        event(new HostelOutPassRequested($record));
                        Notification::make()->title(__('Authentication code dispatched to guardian.'))->success()->send();
                    }),

                Tables\Actions\Action::make('verify_otp')
                    ->label(__('Verify OTP'))
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn ($record) => $record->status === 'pending_parent_otp')
                    ->form([
                        Forms\Components\TextInput::make('otp')->length(6)->required(),
                    ])
                    ->action(function ($record, $data) {
                        $verified = app(OutPassService::class)->verifyOtp($record->school_id, $record->id, $data['otp']);
                        if ($verified) {
                            Notification::make()->title(__('OTP validation successful. Approved for final warden confirmation.'))->success()->send();
                        } else {
                            Notification::make()->title(__('OTP validation failed. Code is invalid.'))->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('warden_approve')
                    ->label(__('Confirm Warden Exit'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->status === 'pending_warden')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'warden_approver_id' => Auth::id(),
                            'warden_approved_at' => now(),
                        ]);
                        Notification::make()->title(__('Out-pass fully approved for scanner checkpoint.'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelOutPasses::route('/'),
            'create' => Pages\CreateHostelOutPass::route('/create'),
            'edit' => Pages\EditHostelOutPass::route('/{record}/edit'),
        ];
    }
}
