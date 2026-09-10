<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PromotionRunResource\Pages;
use App\Filament\App\Resources\PromotionRunResource\RelationManagers;
use App\Services\ModuleVisibilityManager;
use App\Services\Promotion\PromotionService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\AcademicYear;
use Modules\Promotion\Models\PromotionItem;
use Modules\Promotion\Models\PromotionRun;

class PromotionRunResource extends Resource
{
    protected static ?string $model = PromotionRun::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isPageVisible('academics', 'promotion');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    public static function getNavigationLabel(): string
    {
        return __('Promotion Runs');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Promotion Runs');
    }

    public static function getModelLabel(): string
    {
        return __('Promotion Run');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Run Overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('sourceAcademicYear.name')
                            ->label(__('Source Year')),
                        Infolists\Components\TextEntry::make('targetAcademicYear.name')
                            ->label(__('Target Year')),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                PromotionRun::STATUS_DRAFT => 'gray',
                                PromotionRun::STATUS_IN_PROGRESS => 'warning',
                                PromotionRun::STATUS_COMMITTED => 'success',
                                PromotionRun::STATUS_ARCHIVED => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label(__('Created By'))
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Created At'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('committed_at')
                            ->label(__('Committed At'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Preview Summary'))
                    ->schema([
                        Infolists\Components\TextEntry::make('preview_summary')
                            ->label(__('Decisions'))
                            ->state(fn (PromotionRun $record): string => sprintf(
                                'Promoted: %d  ·  Repeated: %d  ·  Needs Screening: %d  ·  Total: %d',
                                $record->previewSummary()['promoted'],
                                $record->previewSummary()['repeated'],
                                $record->previewSummary()['needs_screening'],
                                $record->previewSummary()['total'],
                            )),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sourceAcademicYear.name')
                    ->label(__('Source Year'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('targetAcademicYear.name')
                    ->label(__('Target Year'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PromotionRun::STATUS_DRAFT => 'gray',
                        PromotionRun::STATUS_IN_PROGRESS => 'warning',
                        PromotionRun::STATUS_COMMITTED => 'success',
                        PromotionRun::STATUS_ARCHIVED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('promoted_count')
                    ->label(__('Promoted'))
                    ->state(fn (PromotionRun $record): int => $record->previewSummary()['promoted'])
                    ->alignCenter()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('needs_screening_count')
                    ->label(__('Needs Screening'))
                    ->state(fn (PromotionRun $record): int => $record->previewSummary()['needs_screening'])
                    ->alignCenter()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('Created By'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('committed_at')
                    ->label(__('Committed'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('commit')
                    ->label(__('Commit'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === PromotionRun::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (PromotionRun $record): void {
                        app(PromotionService::class)->commit($record->id, auth()->id());

                        \Filament\Notifications\Notification::make()
                            ->title(__('Promotion run committed'))
                            ->body(__('Enrollments created for the promoted students.'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('undo')
                    ->label(__('Undo'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === PromotionRun::STATUS_COMMITTED)
                    ->requiresConfirmation()
                    ->modalHeading(__('Undo Promotion Run'))
                    ->modalDescription(__('Are you sure you want to undo this committed promotion run? This will remove target enrollments and revert source student enrollments back to active status.'))
                    ->action(function (PromotionRun $record): void {
                        app(PromotionService::class)->undo($record->id, auth()->id());

                        \Filament\Notifications\Notification::make()
                            ->title(__('Promotion run undone'))
                            ->body(__('Enrollments reverted and run returned to draft.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotionRuns::route('/'),
            'view' => Pages\ViewPromotionRun::route('/{record}'),
        ];
    }
}