<?php

namespace App\Filament\App\Resources\HomeworkResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage; // FIX: Added explicit Storage facade import

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Student Submissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('grade_obtained')
                    ->numeric()
                    ->label(__('Grade Obtained (%)'))
                    ->placeholder(__('e.g. 85.00')),

                Forms\Components\Textarea::make('teacher_feedback')
                    ->label(__('Teacher Feedback'))
                    ->rows(3)
                    ->placeholder(__('Enter evaluation comments...')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student Name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label(__('Submitted Date'))
                    ->dateTime('d-M-Y H:i'),

                Tables\Columns\TextColumn::make('grade_obtained')
                    ->label(__('Grade'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? $state.'%' : 'Pending Grading')
                    ->color(fn ($state) => $state >= 50 ? 'success' : 'danger'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Grade'))
                    ->icon('heroicon-o-academic-cap')
                    ->color('success'),

                // Direct file attachment download action using imported facade
                Tables\Actions\Action::make('downloadFile')
                    ->label(__('Download Work'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn ($record) => $record->file_path ? Storage::url($record->file_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->file_path)),
            ]);
    }
}
