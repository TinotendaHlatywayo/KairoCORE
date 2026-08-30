<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SystemAuditLogResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Admin\Models\SystemAuditLog;
use Modules\Admin\Services\PermissionRegistry;

class SystemAuditLogResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('System Administration');
    }

    protected static ?string $model = SystemAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'System Administration';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 15;

    // Reached via the module contextual tabs, not the sidebar.

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.clear_caches');
    }

    /**
     * Beautiful structured Infolist detail panel (Fixes empty/blank View modals) [1]
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('General Metadata')
                    ->schema([
                        TextEntry::make('created_at')->label(__('Timestamp'))->dateTime(),
                        TextEntry::make('user.name')->label(__('Administrator Account'))->default('System Autonomic Engine'),
                        TextEntry::make('action')->label(__('Action Performed')),
                        TextEntry::make('module')->label(__('SaaS Module Segment')),
                        TextEntry::make('ip_address')->label(__('Request IP Source')),
                        TextEntry::make('outcome')->label(__('Outcome State'))->badge()->color(fn ($state) => $state === 'success' ? 'success' : 'danger'),
                    ])->columns(2),

                Section::make('JSON Structural Changes')
                    ->schema([
                        KeyValueEntry::make('old_values')->label(__('Original Database State'))->columnSpan(1),
                        KeyValueEntry::make('new_values')->label(__('Updated / Changes Saved'))->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Timestamp'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('Administrator'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('Action Performed'))
                    ->searchable(),
                TextColumn::make('module')
                    ->label(__('Operational Module')),
                TextColumn::make('ip_address')
                    ->label(__('IP Source'))
                    ->searchable(),
                TextColumn::make('outcome')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray'
                    }),
            ])
            ->filters([
                // Filter 1: Dropdown Modules [1]
                Tables\Filters\SelectFilter::make('module')
                    ->options([
                        'System Administration' => __('System Administration'),
                        'Academics' => __('Academics &SIS'),
                        'Finance' => __('Finance & Cohorts'),
                        'Boarding' => __('Boarding & Welfare'),
                        'Clinic' => __('Clinic & Health'),
                    ]),

                // Filter 2: Specific IP Search Block [1]
                Tables\Filters\Filter::make('ip_address')
                    ->form([
                        TextInput::make('ip_address')
                            ->label(__('Search Specific IP Source'))
                            ->placeholder(__('e.g., 127.0.0.1')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['ip_address'], fn ($q, $ip) => $query->where('ip_address', 'like', "%{$ip}%"));
                    }),

                // Filter 3: Date Range Tracker [1]
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('logged_from')->label(__('Logged From')),
                        DatePicker::make('logged_until')->label(__('Logged Until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['logged_from'], fn ($q, $from) => $query->whereDate('created_at', '>=', $from))
                            ->when($data['logged_until'], fn ($q, $until) => $query->whereDate('created_at', '<=', $until));
                    }),
            ])
            ->headerActions([
                // Custom CSV Stream Export Action [1.2]
                Tables\Actions\Action::make('exportCsv')
                    ->label(__('Export Filtered CSV'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Tables\Contracts\HasTable $livewire) {
                        $query = $livewire->getFilteredTableQuery();

                        return response()->streamDownload(function () use ($query) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Timestamp', 'User', 'Action', 'Module', 'IP Address', 'Outcome']);

                            $query->chunk(200, function ($logs) use ($handle) {
                                foreach ($logs as $log) {
                                    fputcsv($handle, [
                                        $log->created_at->toDateTimeString(),
                                        $log->user?->name ?? 'System',
                                        $log->action,
                                        $log->module,
                                        $log->ip_address,
                                        $log->outcome,
                                    ]);
                                }
                            });
                            fclose($handle);
                        }, 'system-audit-logs-'.date('Y-m-d-His').'.csv');
                    }),

                // Custom HTML print statement (can be saved as PDF easily using Ctrl + P) [1.2]
                Tables\Actions\Action::make('exportPdf')
                    ->label(__('Export Print View'))
                    ->icon('heroicon-o-document-chart-bar')
                    ->action(function (Tables\Contracts\HasTable $livewire) {
                        $logs = $livewire->getFilteredTableQuery()->latest()->limit(500)->get();

                        $html = '<html><head><style>
                            body { font-family: system-ui, sans-serif; font-size: 11px; color: #1e293b; }
                            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                            th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
                            th { background-color: #f1f5f9; font-weight: bold; }
                            .header { text-align: center; margin-bottom: 25px; }
                        </style></head><body>
                        <div class="header">
                            <h2>Kairo CORE Administrative Auditing Statement</h2>
                            <p>Query extraction compiled on: '.date('Y-m-d H:i:s').'</p>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Administrator</th>
                                    <th>Action Performed</th>
                                    <th>Module</th>
                                    <th>IP Source</th>
                                </tr>
                            </thead>
                            <tbody>';
                        foreach ($logs as $log) {
                            $html .= '<tr>
                                <td>'.$log->created_at.'</td>
                                <td>'.($log->user?->name ?? 'System').'</td>
                                <td>'.$log->action.'</td>
                                <td>'.$log->module.'</td>
                                <td>'.$log->ip_address.'</td>
                            </tr>';
                        }
                        $html .= '</tbody></table></body></html>';

                        return response()->streamDownload(function () use ($html) {
                            echo $html;
                        }, 'system-audit-statement-'.date('Y-m-d-His').'.html');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemAuditLogs::route('/'),
        ];
    }
}
