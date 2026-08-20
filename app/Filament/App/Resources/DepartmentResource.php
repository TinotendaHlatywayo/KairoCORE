<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DepartmentResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Department;
use Modules\Admin\Services\PermissionRegistry;

class DepartmentResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('System Administration');
    }

    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Departments';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 5;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.manage_users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Department Name'))
                    ->required(),
                TextInput::make('code')
                    ->label(__('Department Code'))
                    ->required()
                    ->placeholder(__('e.g., DEPT-MATHS')),
                Select::make('type')
                    ->label(__('Classification Category'))
                    ->options([
                        'academic' => __('Academic Department'),
                        'administrative' => __('Administrative Department'),
                        'support' => __('Support / Auxiliary'),
                    ])->required(),

                // Custom select query: fetches heads strictly from physical employees ledger [1.2]
                Select::make('head_user_id')
                    ->label(__('Departmental Head'))
                    ->options(function () {
                        $schoolId = session('current_tenant')?->id;
                        if (! $schoolId) {
                            return [];
                        }

                        return DB::table('employees')
                            ->where('school_id', $schoolId)
                            ->get()
                            ->mapWithKeys(fn ($emp) => [$emp->id => "{$emp->first_name} {$emp->last_name}"])
                            ->toArray();
                    })
                    ->searchable(),
                TextInput::make('budget_code')
                    ->label(__('Financial Budget Code'))
                    ->placeholder(__('e.g., BC-401-02')),
                Select::make('status')
                    ->label(__('Departmental State'))
                    ->options([
                        'active' => __('Active Operating State'),
                        'suspended' => __('Suspended / Inactive'),
                    ])->default('active'),
                CheckboxList::make('permissions')
                    ->label(__('Default Permissions'))
                    ->helperText(__('Every user assigned to this department inherits these permissions (e.g., a clinic member gets clinic rights). Adjust per department — individual accounts can be further tailored at approval.'))
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
                TextColumn::make('code')->searchable(),
                TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'academic' => 'success',
                    'administrative' => 'warning',
                    'support' => 'info',
                }),
                TextColumn::make('head.first_name')
                    ->label(__('Head of Department'))
                    ->formatStateUsing(fn ($record) => $record->head ? "{$record->head->first_name} {$record->head->last_name}" : 'Unassigned'),
                TextColumn::make('budget_code')->label(__('Budget Center Code')),
                TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
        ];
    }
}
