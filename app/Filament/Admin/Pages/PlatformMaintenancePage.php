<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlatformMaintenancePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Platform Maintenance';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    protected static string $view = 'modules.saas.maintenance';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $osName = php_uname('s');
        $dbVer = 'MySQL';

        try {
            $verQuery = DB::select('select version() as ver');
            if (! empty($verQuery)) {
                $dbVer = $verQuery[0]->ver;
            }
        } catch (\Exception $e) {
            // Fallback for isolated local test drivers
        }

        $queueSize = 0;
        try {
            $queueSize = DB::table('jobs')->count();
        } catch (\Exception $e) {
            // Fallback if jobs ledger table is truncated
        }

        $this->form->fill([
            'php_version' => PHP_VERSION,
            'db_version' => $dbVer,
            'os_info' => $osName,
            'maintenance_mode' => app()->isDownForMaintenance(),
            'queue_size' => $queueSize,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('MaintenanceTabs')
                    ->tabs([
                        Tab::make('Server Performance Metrics')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Placeholder::make('php_version')
                                    ->label(__('PHP Language Runtime Engine'))
                                    ->content(fn () => $this->data['php_version'] ?? PHP_VERSION),
                                Placeholder::make('db_version')
                                    ->label(__('Relational Database Version'))
                                    ->content(fn () => $this->data['db_version'] ?? 'MySQL'),
                                Placeholder::make('os_info')
                                    ->label(__('Operational System Kernel'))
                                    ->content(fn () => $this->data['os_info'] ?? 'Linux'),
                                Placeholder::make('queue_size')
                                    ->label(__('Current Queue Length'))
                                    ->content(fn () => ($this->data['queue_size'] ?? 0).' active workers in loop'),
                            ])->columns(2),

                        Tab::make('Platform Switches')
                            ->icon('heroicon-o-adjustments-vertical')
                            ->schema([
                                Toggle::make('maintenance_mode')
                                    ->label(__('Platform-Wide Maintenance Window'))
                                    ->helperText(__('Activating this suspends user logins across all school subdomains instantly.'))
                                    ->default(false),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if (isset($state['maintenance_mode']) && $state['maintenance_mode']) {
            touch(storage_path('framework/down'));
        } else {
            @unlink(storage_path('framework/down'));
        }

        Notification::make()
            ->title(__('Maintenance Parameters Updated'))
            ->success()
            ->send();
    }
}
