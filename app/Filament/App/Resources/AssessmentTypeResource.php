<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssessmentTypeResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\PermissionRegistry;

class AssessmentTypeResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = AssessmentType::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_assessments');
        }

        return true;
    }

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Add New Test / Assessment';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assessment Configuration Details')
                    ->description(__('Define custom test names, marks scopes, and grade weighting configurations.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Assessment / Test Name'))
                            ->required()
                            ->placeholder(__('e.g. End of Month 1, Topic 2 Quiz, BOT Exam')),

                        Forms\Components\Select::make('term_id')
                            ->label(__('Academic Term'))
                            ->options(function () {
                                $activeYear = AcademicYear::where('is_active', true)->first();
                                if (! $activeYear) {
                                    return [];
                                }

                                return Term::where('academic_year_id', $activeYear->id)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->preload()
                            ->placeholder(__('Select Term...')),

                        Forms\Components\TextInput::make('max_mark')
                            ->label(__('Max Attainable Mark'))
                            ->numeric()
                            ->default(100.00)
                            ->required()
                            ->placeholder(__('e.g. 50, 100')),

                        Forms\Components\TextInput::make('weight_percentage')
                            ->label(__('Layout Weight towards Final Term Grade (%)'))
                            ->numeric()
                            ->default(20.00)
                            ->required()
                            ->placeholder(__('e.g. 20, 40')),
                    ])->columns(2),

                Forms\Components\Section::make('Assessment Scope Constraints (Optional)')
                    ->description(__('Leave scope values empty if this test applies globally to all levels and subjects.'))
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label(__('Restrict to Specific Subject'))
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Subjects')),

                        Forms\Components\Select::make('course_id')
                            ->label(__('Restrict to Specific Grade / Form'))
                            ->options(Course::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Form Levels'))
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Restrict to Specific Class Stream'))
                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Streams')),
                    ])->columns(3),

                // Auto-inject system parameters silently on form creation
                Forms\Components\Hidden::make('created_by_id')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('school_id')
                    ->default(fn () => app('current_tenant')->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ADDED: Assessment ID Column for easy CSV mapping reference
                Tables\Columns\TextColumn::make('id')
                    ->label(__('Assessment ID'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Assessment Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_mark')
                    ->label(__('Max Mark'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('weight_percentage')
                    ->label(__('Weight %'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject Scope'))
                    ->default('Global (All Subjects)')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('course.name')
                    ->label(__('Form Scope'))
                    ->default('Global (All Forms)')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Stream Scope'))
                    ->default('Global (All Streams)')
                    ->color('gray'),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessmentTypes::route('/'),
            'create' => Pages\CreateAssessmentType::route('/create'),
            'edit' => Pages\EditAssessmentType::route('/{record}/edit'),
        ];
    }
}
