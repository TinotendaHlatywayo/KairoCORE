<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Mail\AnnouncementPublishedMail;
use App\Models\User;
use App\Security\RoleCatalogue;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
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
                        ->extraAttributes(['class' => 'overflow-visible!'])
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => __('Draft'),
                                    'scheduled' => __('Scheduled'),
                                    'published' => __('Published'),
                                    'expired' => __('Expired'),
                                ])->required()->default('published'),
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
                            Forms\Components\Select::make('channel')
                                ->label(__('Delivery Channel'))
                                ->options([
                                    'system_only' => __('System only'),
                                    'email_only' => __('Email only'),
                                    'both' => __('System and Email'),
                                ])
                                ->default('system_only')
                                ->helperText(__('System delivers an in-app notification inside each user\'s portal; Email sends the full notice to matching addresses.')),
                        ]),

                    Forms\Components\Section::make(__('Audience Targets'))
                        ->extraAttributes(['class' => 'overflow-visible!'])
                        ->schema([
                            Forms\Components\Select::make('visibility')
                                ->label(__('Visible to Roles'))
                                ->multiple()
                                ->options(fn (): array => RoleCatalogue::audienceRoleOptions())
                                ->preload(),
                            Forms\Components\Select::make('target_user_ids')
                                ->label(__('Target Specific Individuals'))
                                ->multiple()
                                ->options(fn () => User::where('school_id', auth()->user()?->school_id)->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
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
                Tables\Columns\TextColumn::make('content')
                    ->label(__('Message'))
                    ->html()
                    ->limit(120)
                    ->tooltip(function ($record) {
                        return strip_tags((string) $record->content);
                    }),
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

                        static::broadcastToAudience($record);

                        Notification::make()
                            ->title(__('Notice Published and Broadcasted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Resolve the users a notice is aimed at and fan an in-app notification out
     * to exactly those users (plus an email hook for the channel setting).
     *
     * Roles are matched against the real role storage (`requested_role` and
     * `custom_roles.role_key`), not a non-existent `users.role` column, so a
     * role-targeted notice actually reaches its audience.
     *
     * @return Collection<int, User>
     */
    public static function broadcastToAudience(Announcement $record)
    {
        $usersQuery = User::query()->where('school_id', $record->school_id);

        $visibility = $record->visibility ?? [];
        $targetUserIds = $record->target_user_ids ?? [];

        if (! empty($visibility) || ! empty($targetUserIds)) {
            $usersQuery->where(function ($q) use ($visibility, $targetUserIds) {
                if (! empty($visibility)) {
                    $q->where(function ($roleQ) use ($visibility) {
                        $roleQ->whereIn('requested_role', $visibility)
                            ->orWhereHas('customRole', fn ($r) => $r->whereIn('role_key', $visibility));
                    });
                }

                if (! empty($targetUserIds)) {
                    $q->orWhereIn('id', $targetUserIds);
                }
            });
        }

        $notifiedUsers = $usersQuery->get();

        $channel = $record->channel ?? 'system_only';
        $notifyInApp = in_array($channel, ['system_only', 'both'], true);
        $notifyByEmail = in_array($channel, ['email_only', 'both'], true);

        foreach ($notifiedUsers as $user) {
            if ($notifyInApp) {
                Notification::make()
                    ->title(__('New Notice Published'))
                    ->body(__('Important Announcement: ').$record->title)
                    ->success()
                    ->sendToDatabase($user);
            }
        }

        if ($notifyByEmail) {
            try {
                foreach ($notifiedUsers->whereNotNull('email') as $user) {
                    Mail::to($user->email)->send(new AnnouncementPublishedMail(
                        title: $record->title,
                        content: str_replace('&nbsp;', ' ', (string) preg_replace('/<p[^>]*>|<\/p>/i', '', (string) $record->content)),
                        schoolName: $record->school->name ?? '',
                    ));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $notifiedUsers;
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
    use HasPageHelp;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make()->label(__('Create Notice')),
        ];
    }
}
class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->status === 'published') {
            AnnouncementResource::broadcastToAudience($this->record);
        }
    }
}
class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterSave(): void
    {
        if ($this->record->status === 'published'
            && ($this->record->wasChanged('status')
                || $this->record->wasChanged('visibility')
                || $this->record->wasChanged('target_user_ids'))) {
            AnnouncementResource::broadcastToAudience($this->record);
        }
    }
}
