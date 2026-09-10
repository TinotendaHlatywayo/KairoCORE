<?php

namespace App\Filament\App\Resources\ScreeningRunResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Screening\Models\ScreeningItem;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Screening Items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.admission_number')
                    ->label(__('Admission #'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('overall_score')
                    ->label(__('Overall Score %'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('decision')
                    ->label(__('Decision'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ScreeningItem::DECISION_PLACED => 'success',
                        ScreeningItem::DECISION_UNPLACED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('targetCourse.name')
                    ->label(__('Target Level'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('targetSection.name')
                    ->label(__('Target Class'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40)
                    ->placeholder('-'),
            ])
            ->bulkActions([]);
    }
}