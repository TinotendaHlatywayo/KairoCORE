<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\GeneratedReportResource\Pages;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Services\ReportAuditService;

class GeneratedReportResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Reports & Intelligence');
    }

    protected static ?string $model = GeneratedReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    protected static ?string $navigationLabel = 'Report Archive';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Compiled Filename'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('format')
                    ->label(__('File Extension'))
                    ->badge()
                    ->color(fn ($state) => $state === 'pdf' ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('record_count')
                    ->label(__('Record Metrics'))
                    ->numeric(),

                TextColumn::make('execution_ms')
                    ->label(__('Execution'))
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((int) $state).' ms')
                    ->toggleable(),

                TextColumn::make('data_validated')
                    ->label(__('Data Accuracy'))
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn (GeneratedReport $record) => match (true) {
                        $record->validated_at === null => 'Not verified',
                        $record->data_validated => 'Verified',
                        default => 'Data changed since compilation',
                    })
                    ->color(fn (GeneratedReport $record) => match (true) {
                        $record->validated_at === null => 'gray',
                        $record->data_validated => 'success',
                        default => 'warning',
                    })
                    ->tooltip(fn (GeneratedReport $record) => $record->validated_at
                        ? 'Last verified '.$record->validated_at->format('M d, Y H:i')
                        : 'Re-run to confirm this report still matches live source data'),

                TextColumn::make('generator.name')
                    ->label(__('Compiled By'))
                    ->placeholder(__('System Account')),

                TextColumn::make('created_at')
                    ->label(__('Timestamp Generated'))
                    ->dateTime('M d, Y H:i'),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('Download'))
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color('primary')
                    ->visible(fn (GeneratedReport $record) => $record->status === 'completed' && ! empty($record->file_path))
                    ->url(fn (GeneratedReport $record) => asset('storage/'.$record->file_path))
                    ->openUrlInNewTab(),

                Action::make('verify')
                    ->label(fn (GeneratedReport $record) => $record->validated_at ? 'Re-verify data' : 'Verify data accuracy')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Verify data accuracy')
                    ->modalDescription('Re-runs the underlying query against the current database and compares the compiled checksum. Flags the report if source data changed after it was generated.')
                    ->visible(fn (GeneratedReport $record) => $record->status === 'completed' && $record->data_checksum !== null)
                    ->action(function (GeneratedReport $record) {
                        $valid = app(ReportAuditService::class)->verify($record);

                        Notification::make()
                            ->{$valid ? 'success' : 'warning'}()
                            ->title($valid ? 'Data verified' : 'Source data has changed')
                            ->body($valid
                                ? 'The report still matches the current database.'
                                : 'This report was compiled from older data. Regenerate it to reflect the current database.')
                            ->send();
                    }),

                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneratedReports::route('/'),
        ];
    }
}
