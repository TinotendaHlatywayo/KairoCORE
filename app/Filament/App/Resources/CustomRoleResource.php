<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\CustomRoleResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
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

class CustomRoleResource extends Resource
{
    use ModulePermissionAccess;

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

    public static function form(Form $form): Form
    {
        $tabs = array_merge([
            Tab::make(__('General Information'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    TextInput::make('name')
                        ->label(__('Role Designation Name'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->label(__('Role Responsibility Description')),
                    CheckboxList::make('permissions_special')
                        ->label(__('Special Privileges'))
                        ->options([
                            '*' => __('System Administrator (Full Wildcard Access — Every module, page & operation)'),
                            'student_portal.access' => __('Student Portal Access Only'),
                        ])
                        ->helperText(__('Select wildcard or portal access if this role bypasses module-level granularity.')),
                ]),
        ], self::permissionEditorTabs('permissions'));

        return $form
            ->schema([
                Tabs::make('RoleEditor')
                    ->tabs($tabs)
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
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->mutateRecordDataUsing(fn (array $data): array => self::hydratePermissions($data))
                    ->mutateFormDataUsing(fn (array $data): array => self::dehydratePermissions($data)),
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
