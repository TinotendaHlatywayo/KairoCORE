<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\Communication\Models\CampusResource;

class CampusResourceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = CampusResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Campus Resource';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the Communication Center contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Resource Properties')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->options([
                                'academic' => __('Academic Curriculum'),
                                'finance' => __('Finance & Invoicing'),
                                'hr' => __('Human Resources Policies'),
                                'examinations' => __('Examinations Syllabus'),
                                'tutorials' => __('Video Tutorials'),
                                'downloadable' => __('Office Downloads'),
                            ])->required(),
                        Forms\Components\TagsInput::make('tags')
                            ->separator(','),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Security & Files')
                        ->schema([
                            Forms\Components\TextInput::make('version')
                                ->default('1.0')
                                ->required(),
                            Forms\Components\FileUpload::make('file_path')
                                ->label(__('Asset File'))
                                ->disk('public')
                                ->directory('communication/resources')
                                ->maxSize(10240)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'image/jpeg',
                                    'image/png',
                                    'image/jpg',
                                ])
                                ->required(),
                            Forms\Components\FileUpload::make('thumbnail_path')
                                ->label(__('Cover Image'))
                                ->image()
                                ->disk('public')
                                ->directory('communication/thumbnails'),
                            Forms\Components\Select::make('visibility')
                                ->label(__('Download Eligibility'))
                                ->multiple()
                                ->options([
                                    'admin' => __('Administrators'),
                                    'teacher' => __('Teachers'),
                                    'student' => __('Students'),
                                    'parent' => __('Parents'),
                                ])->preload(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // COVER IMAGE TABLE PREVIEW
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label(__('Cover'))
                    ->disk('public')
                    ->square()
                    ->size(40),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\TextColumn::make('download_count')
                    ->label(__('Downloads'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('download')
                    ->label(__('Download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        $record->increment('download_count');

                        Notification::make()
                            ->title(__('Download Initiated'))
                            ->success()
                            ->send();

                        return response()->download(storage_path('app/public/'.$record->file_path));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampusResources::route('/'),
            'create' => CreateCampusResource::route('/create'),
            'edit' => EditCampusResource::route('/{record}/edit'),
        ];
    }
}

class ListCampusResources extends ListRecords
{
    protected static string $resource = CampusResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Resource')),
        ];
    }
}
class CreateCampusResource extends CreateRecord
{
    protected static string $resource = CampusResourceResource::class;
}
class EditCampusResource extends EditRecord
{
    protected static string $resource = CampusResourceResource::class;
}
