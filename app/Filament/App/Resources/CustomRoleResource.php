<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CustomRoleResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\AuditLogger;
use Modules\Admin\Services\PermissionRegistry;

class CustomRoleResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('System Administration');
    }

    protected static ?string $model = CustomRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 4;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.manage_security');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Role Designation Name'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label(__('Role Responsibility Description')),
                CheckboxList::make('permissions')
                    ->label(__('Access Privileges'))
                    ->helperText(__('Select the granular permissions this role grants. Each permission is listed as "Module — Action".'))
                    ->options(fn () => PermissionRegistry::permissionOptions())
                    ->columns(3)
                    ->gridDirection('row')
                    ->searchable()
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('description')->limit(50),
                IconColumn::make('is_system')
                    ->label(__('System Default'))
                    ->boolean()
                    ->trueColor('text-emerald-500')
                    ->falseColor('text-gray-300'),
                TextColumn::make('users_count')
                    ->label(__('Assigned Directory Users'))
                    ->counts('users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Action::make('clone')
                    ->label(__('Clone'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function (CustomRole $record) {
                        $clone = $record->replicate();
                        $clone->name = $record->name.' - Copy';
                        $clone->is_system = false;
                        $clone->save();

                        AuditLogger::log('Clone Custom Role', 'System Administration', null, ['original' => $record->name, 'new' => $clone->name]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function (CustomRole $record) {
                        if ($record->is_system) {
                            throw new \Exception('Cannot delete default system administration roles.');
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomRoles::route('/'),
        ];
    }
}
