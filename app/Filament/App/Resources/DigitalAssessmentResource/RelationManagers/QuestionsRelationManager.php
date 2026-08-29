<?php

namespace App\Filament\App\Resources\DigitalAssessmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\DigitalAssessment\Enums\QuestionDifficulty;
use Modules\DigitalAssessment\Enums\QuestionType;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\DigitalAssessment\Services\DigitalAssessmentService;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Assessment Questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('question_bank_id')
                    ->label('Question')
                    ->options(fn () => QuestionBank::where('school_id', current_tenant()?->id)
                        ->where('status', 'published')
                        ->get()
                        ->mapWithKeys(fn ($q) => [
                            $q->id => $q->title.' ['.$q->question_type->label().'] — '.$q->subject?->name,
                        ])
                        ->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('question_order')
                    ->label('Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(1),

                Forms\Components\TextInput::make('marks_override')
                    ->label('Marks Override')
                    ->numeric()
                    ->step(0.25)
                    ->minValue(0)
                    ->placeholder('Use question default'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('question.title')
                    ->label('Question')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->question?->title ?? ''),

                Tables\Columns\TextColumn::make('question.question_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \BackedEnum) {
                            return $state->label();
                        }

                        return QuestionType::tryFrom((string) $state)?->label() ?? $state;
                    }),

                Tables\Columns\TextColumn::make('question.difficulty')
                    ->label('Difficulty')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $difficulty = $state instanceof \BackedEnum
                            ? $state
                            : QuestionDifficulty::tryFrom($state);

                        return $difficulty?->label() ?? $state;
                    })
                    ->color(function ($state) {
                        $difficulty = $state instanceof \BackedEnum
                            ? $state
                            : QuestionDifficulty::tryFrom($state);

                        return $difficulty?->color() ?? 'gray';
                    }),

                Tables\Columns\TextColumn::make('question.subject.name')
                    ->label('Subject'),

                Tables\Columns\TextColumn::make('effective_marks')
                    ->label('Marks')
                    ->getStateUsing(fn ($record) => number_format($record->getEffectiveMarks(), 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('question.marks')
                    ->label('Default Marks')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('question_order', 'asc')
            ->headerActions([
                Tables\Actions\Action::make('addFromBank')
                    ->label('Add from Question Bank')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('question_ids')
                            ->label('Select Questions')
                            ->multiple()
                            ->options(fn () => QuestionBank::where('school_id', current_tenant()?->id)
                                ->where('status', 'published')
                                ->get()
                                ->mapWithKeys(fn ($q) => [
                                    $q->id => $q->title.' ['.$q->question_type->label().'] — '.$q->subject?->name,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('starting_order')
                            ->label('Starting Order Position')
                            ->numeric()
                            ->default(fn ($record) => $record?->questions()->max('question_order') + 1 ?? 1)
                            ->minValue(1),
                    ])
                    ->action(function (array $data, RelationManager $manager): void {
                        $assessment = $manager->getRecord();
                        $service = app(DigitalAssessmentService::class);

                        $service->attachQuestions($assessment, $data['question_ids']);

                        Notification::make()
                            ->title(count($data['question_ids']).' question(s) added')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('moveUp')
                    ->icon('heroicon-o-arrow-up')
                    ->iconButton()
                    ->tooltip('Move Up')
                    ->action(function ($record) {
                        if ($record->question_order > 1) {
                            $prev = $record->assessment->questions()
                                ->where('question_order', '<', $record->question_order)
                                ->orderByDesc('question_order')
                                ->first();

                            if ($prev) {
                                $tempOrder = $record->question_order;
                                $record->update(['question_order' => $prev->question_order]);
                                $prev->update(['question_order' => $tempOrder]);
                            }
                        }
                    }),

                Tables\Actions\Action::make('moveDown')
                    ->icon('heroicon-o-arrow-down')
                    ->iconButton()
                    ->tooltip('Move Down')
                    ->action(function ($record) {
                        $maxOrder = $record->assessment->questions()->max('question_order');
                        if ($record->question_order < $maxOrder) {
                            $next = $record->assessment->questions()
                                ->where('question_order', '>', $record->question_order)
                                ->orderBy('question_order')
                                ->first();

                            if ($next) {
                                $tempOrder = $record->question_order;
                                $record->update(['question_order' => $next->question_order]);
                                $next->update(['question_order' => $tempOrder]);
                            }
                        }
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
