<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources;

use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Filament\Resources\LibraryIssueResource\Pages\CreateLibraryIssue;
use Modules\Library\Filament\Resources\LibraryIssueResource\Pages\ListLibraryIssues;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryBookCopy;
use Modules\Library\Models\LibraryIssue;
use Modules\Students\Models\Student;

class LibraryIssueResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Library');
    }

    protected static ?string $model = LibraryIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Circulation Desk';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Library';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('library');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Issue Processing Form'))
                    ->schema([
                        Forms\Components\Select::make('library_book_id')
                            ->label(__('Select Resource Title'))
                            ->placeholder(__('Type title, author name, or format class to search...'))
                            ->options(function () {
                                return LibraryBook::where('media_type', 'physical')
                                    ->with('format')
                                    ->get()
                                    ->mapWithKeys(function ($book) {
                                        $formatName = isset($book->format->name) ? $book->format->name : __('General Book');
                                        $avail = $book->getAvailableCopiesCount();
                                        $total = $book->getTotalCopiesCount();

                                        return [$book->id => __("{$book->title} [{$formatName}] (Available: {$avail}/{$total})")];
                                    });
                            })
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('library_book_copy_id', null)),

                        Forms\Components\Select::make('library_book_copy_id')
                            ->label(__('Select Copy / Barcode'))
                            ->placeholder(__('Choose an available barcode copy...'))
                            ->options(function (Forms\Get $get) {
                                $bookId = $get('library_book_id');
                                if (! $bookId) {
                                    return [];
                                }

                                return LibraryBookCopy::where('library_book_id', $bookId)
                                    ->where('status', 'available')
                                    ->pluck('barcode', 'id');
                            })
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('student_id')
                            ->label(__('Search Student'))
                            ->placeholder(__('Type student name or ID number...'))
                            ->relationship('student', 'first_name')
                            ->getOptionLabelFromRecordUsing(function (Student $record) {
                                return $record->admission_number.' - '.$record->first_name.' '.$record->last_name;
                            })
                            ->searchable(['first_name', 'last_name', 'admission_number'])
                            ->preload()
                            ->hint(__('Leave blank if borrowing to a staff member')),

                        Forms\Components\Select::make('user_id')
                            ->label(__('Search Staff Borrower'))
                            ->placeholder(__('Type employee name...'))
                            ->relationship('borrowerUser', 'name')
                            ->getOptionLabelFromRecordUsing(function (User $record) {
                                return $record->name.' ('.$record->email.')';
                            })
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->hint(__('Leave blank if borrowing to a student')),

                        Forms\Components\DatePicker::make('issued_at')
                            ->label(__('Borrowed Date'))
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_at')
                            ->label(__('Return Due Date'))
                            ->required()
                            ->default(now()->addDays(14)),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('copy.book.title')
                    ->label(__('Title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('copy.barcode')
                    ->label(__('Barcode'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('student')
                    ->label(__('Student Borrower Info'))
                    ->formatStateUsing(function ($record) {
                        if (! $record || ! $record->student) {
                            return null;
                        }

                        $enrollment = $record->student->enrollments()
                            ->with(['course', 'section'])
                            ->latest()
                            ->first();

                        $className = $enrollment
                            ? $enrollment->course->name.' '.$enrollment->section->name
                            : __('Unassigned Class');

                        return $record->student->first_name.' '.$record->student->last_name.' | ID: '.$record->student->admission_number.' | Class: '.$className;
                    }),

                Tables\Columns\TextColumn::make('borrowerUser')
                    ->label(__('Staff Borrower Info'))
                    ->formatStateUsing(function ($record) {
                        if (! $record || ! $record->borrowerUser) {
                            return null;
                        }

                        $role = isset($record->borrowerUser->role) ? $record->borrowerUser->role : 'Staff Member';

                        return $record->borrowerUser->name.' | Role: '.$role;
                    }),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('Borrowed'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label(__('Due'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLibraryIssues::route('/'),
            'create' => CreateLibraryIssue::route('/create'),
        ];
    }
}
