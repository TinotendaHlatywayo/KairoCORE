<?php

namespace App\Filament\App\Resources\PromotionRunResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promotion\Models\PromotionItem;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Promotion Items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student'))
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('student', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('admission_number', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('student.admission_number')
                    ->label(__('Admission #'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('decision')
                    ->label(__('Decision'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PromotionItem::DECISION_PROMOTED => 'success',
                        PromotionItem::DECISION_REPEATED => 'danger',
                        PromotionItem::DECISION_NEEDS_SCREENING => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('targetCourse.name')
                    ->label(__('Target Level'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('targetSection.name')
                    ->label(__('Target Class'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('sourceEnrollment.roll_number')
                    ->label(__('Roll #'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40)
                    ->placeholder('-'),
            ])
            ->bulkActions([]);
    }
}