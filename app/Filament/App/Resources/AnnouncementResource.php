<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\User;
use Carbon\Carbon;
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
use Illuminate\Database\Eloquent\Builder;
use Modules\Communication\Models\Announcement;

class AnnouncementResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Notice / Announcement';

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
                Forms\Components\Section::make(__('Notice Details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Publication & Priority'))
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => __('Draft'),
                                    'scheduled' => __('Scheduled'),
                                    'published' => __('Published'),
                                    'expired' => __('Expired'),
                                ])->required()->default('draft'),
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => __('Low'),
                                    'normal' => __('Normal'),
                                    'important' => __('Important'),
                                    'critical' => __('Critical'),
                                    'emergency' => __('Emergency'),
                                ])->required()->default('normal'),
                            Forms\Components\Select::make('display_style')
                                ->options([
                                    'card' => __('Standard Card'),
                                    'banner' => __('Alert Banner'),
                                    'popup' => __('Modal Popup'),
                                    'ticker' => __('Scrolling Ticker'),
                                ])->required()->default('card'),
                            Forms\Components\Toggle::make('requires_acknowledgement')
                                ->default(false),
                        ]),

                    Forms\Components\Section::make(__('Audience Targets'))
                        ->schema([
                            Forms\Components\Select::make('visibility')
                                ->label(__('Visible to Roles'))
                                ->multiple()
                                ->options([
                                    'admin' => __('Administrators'),
                                    'teacher' => __('Teachers'),
                                    'student' => __('Students'),
                                    'parent' => __('Parents'),
                                    'accountant' => __('Finance Staff'),
                                    'librarian' => __('Librarians'),
                                ])->preload(),
                            Forms\Components\DatePicker::make('published_at'),
                            Forms\Components\DatePicker::make('expires_at'),
                        ]),

                    Forms\Components\Section::make(__('Attachments'))
                        ->schema([
                            Forms\Components\FileUpload::make('attachments')
                                ->multiple()
                                ->disk('public')
                                ->directory('communication/notices')
                                ->maxSize(2048)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'image/jpeg',
                                    'image/png',
                                    'image/jpg',
                                ])
                                ->validationMessages([
                                    'max' => __('The selected file size is greater than 2MB. To prevent uploading issues, please compress your file by 25%, 50%, or 75% using an image editor before re-trying.'),
                                ])
                                ->label(__('Documents (PDF, Word, Excel, Images)')),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Default filter: Automatically remove expired notices from the main grid view
            ->modifyQueryUsing(function (Builder $query) {
                if (! request()->has('tableFilters.show_history.isActive')) {
                    $query->where('status', 'published')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'primary' => 'normal',
                        'warning' => 'important',
                        'danger' => ['critical', 'emergency'],
                        'secondary' => 'low',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'success' => 'published',
                        'danger' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('Published'))
                    ->date(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('Expiration'))
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return __('Never');
                        }

                        return Carbon::parse($state)->diffForHumans();
                    })
                    ->color(fn ($state) => $state && Carbon::parse($state)->isPast() ? 'danger' : 'gray'),
            ])
            ->filters([
                // ARCHIVE HISTORY TOGGLE BUTTON
                Tables\Filters\Filter::make('show_history')
                    ->label(__('Show History (Include Expired & Drafts)'))
                    ->toggle()
                    ->query(function (Builder $query, array $data) {
                        if ($data['isActive']) {
                            // If toggled, clear default scope restrictions to show all records
                            $query->orWhereNotNull('id');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('publish')
                    ->label(__('Publish'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);

                        $usersQuery = User::where('school_id', $record->school_id);

                        if (! empty($record->visibility)) {
                            $usersQuery->whereIn('role', $record->visibility);
                        }

                        $notifiedUsers = $usersQuery->get();

                        foreach ($notifiedUsers as $user) {
                            Notification::make()
                                ->title(__('New Notice Published'))
                                ->body(__('Important Announcement: ').$record->title)
                                ->success()
                                ->sendToDatabase($user);
                        }

                        Notification::make()
                            ->title(__('Notice Published and Broadcasted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Create Notice')),
        ];
    }
}
class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
}
class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;
}
