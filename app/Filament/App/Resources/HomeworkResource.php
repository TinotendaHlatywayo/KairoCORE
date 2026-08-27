<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HomeworkResource\Pages;
use App\Filament\App\Resources\HomeworkResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Lms\Models\Homework;

class HomeworkResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('LMS');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = Homework::class;

    protected static ?string $navigationGroup = 'LMS';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Homework & Lessons';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // The Class column renders section→course names; eager loading keeps
        // strict mode (Model::shouldBeStrict) from flagging N+1 lazy loads.
        return parent::getEloquentQuery()->with(['section.course', 'subject']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make(__('Homework Details'))
                            ->schema([
                                // 1. Select ANY Class Stream configured in the school
                                Forms\Components\Select::make('section_id')
                                    ->label(__('Class Stream'))
                                    ->options(function () {
                                        return Section::with('course')->get()->pluck('full_name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('Select Class Stream (e.g. Form 2A)...')),

                                // 2. Select ANY Subject configured in the school
                                Forms\Components\Select::make('subject_id')
                                    ->label(__('Subject'))
                                    ->options(function () {
                                        return Subject::all()->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('Select Subject...')),

                                Forms\Components\TextInput::make('title')
                                    ->label(__('Assignment Title'))
                                    ->required()
                                    ->placeholder(__('e.g. Quadratic Equations Exercise 4')),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label(__('Submission Due Date'))
                                    ->required()
                                    ->default(now()->addDays(2)),

                                Forms\Components\Textarea::make('description')
                                    ->label(__('Instructions / Study Guide'))
                                    ->rows(4)
                                    ->placeholder(__('Enter instructions for students or parent supervision guidance...')),
                            ])->columnSpan(2),

                        Forms\Components\Section::make(__('Attachments & Materials'))
                            ->description(__('Upload files or attach learning videos.'))
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->label(__('Study Guide / Worksheet'))
                                    ->directory('homework-materials')
                                    ->visibility('private')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                                    ->maxSize(5120),

                                Forms\Components\TextInput::make('youtube_url')
                                    ->label(__('YouTube Lesson Link'))
                                    ->url()
                                    ->placeholder(__('https://youtube.com/watch?v=...')),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Class'))
                    ->formatStateUsing(fn ($record) => ($record->section?->course?->name ?? '').' '.($record->section?->name ?? ''))
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('Due Date'))
                    ->date('d-M-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label(__('Submissions'))
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),

                Tables\Actions\Action::make('shareWhatsApp')
                    ->label(__('Share'))
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->iconButton()
                    ->tooltip(__('Share Assignment to WhatsApp Group'))
                    ->action(function ($record) {
                        $class = ($record->section?->course?->name ?? '').' '.($record->section?->name ?? '');
                        $subject = $record->subject?->name ?? '';
                        $dueDate = $record->due_date->format('d-M-Y');

                        $message = urlencode(
                            "*📢 SCHOOL CORE - NEW HOMEWORK NOTICE*\n".
                            "-----------------------------------------\n".
                            "*Class:* {$class}\n".
                            "*Subject:* {$subject}\n".
                            "*Assignment:* {$record->title}\n".
                            "*Due Date:* {$dueDate}\n".
                            "-----------------------------------------\n".
                            'Please ensure homework is completed on time. Logs & worksheets can be accessed by logging into your student portal.'
                        );

                        return redirect()->away("https://api.whatsapp.com/send?text={$message}");
                    }),

                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeworks::route('/'),
            'create' => Pages\CreateHomework::route('/create'),
            'edit' => Pages\EditHomework::route('/{record}/edit'),
        ];
    }
}
