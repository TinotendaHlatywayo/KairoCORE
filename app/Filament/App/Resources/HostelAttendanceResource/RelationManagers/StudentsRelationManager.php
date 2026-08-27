<?php

namespace App\Filament\App\Resources\HostelAttendanceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelAttendanceStudent;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'Student Attendance';

    protected static ?string $recordTitleAttribute = 'student.name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => HostelAllocation::query()
                        ->where('school_id', app('current_tenant')->id)
                        ->where('status', 'active')
                        ->whereHas('bed.room', fn ($q) => $q->where('hostel_id', $this->ownerRecord->hostel_id))
                        ->with('student')
                        ->get()
                        ->mapWithKeys(fn ($a) => [$a->student_id => "{$a->student->first_name} {$a->student->last_name}"])
                        ->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'leave' => 'On Leave',
                    ])
                    ->default('present')
                    ->required(),

                Forms\Components\Textarea::make('remarks')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($state, $record) => "{$record->student->first_name} {$record->student->last_name}"),
                Tables\Columns\TextColumn::make('student.section.name')
                    ->label('Class'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'info' => 'leave',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('remarks')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('notified_parents_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Parents Notified'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'leave' => 'On Leave',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Mark Attendance')
                    ->modalHeading('Record Student Attendance'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}