<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformMessageResource\Pages\ListPlatformMessages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Services\PlatformMessagingService;

class PlatformMessageResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Communication');
    }

    protected static ?string $model = PlatformMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Tenant Messages';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Gmail-style conversations: ONE table row per thread (the newest
        // message represents it), plus eager loads used by columns/actions.
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['school', 'recipients.school'])
            ->addSelect([
                'platform_messages.*',
                'thread_size' => PlatformMessage::query()
                    ->withoutGlobalScopes()
                    ->selectRaw('count(*)')
                    ->whereColumn('thread_id', 'platform_messages.thread_id'),
            ])
            ->whereIn('id', PlatformMessage::query()
                ->withoutGlobalScopes()
                ->selectRaw('max(id)')
                ->groupBy('thread_id'));
    }

    /**
     * The school this conversation belongs to (for single-tenant threads):
     * the latest message's own school, any sibling's school, or recipient rows.
     */
    public static function resolveThreadSchool(PlatformMessage $record): ?School
    {
        return $record->school
            ?? $record->recipients->first()?->school
            ?? PlatformMessage::withoutGlobalScopes()
                ->where('thread_id', $record->thread_id)
                ->whereNotNull('school_id')
                ->with('school')
                ->get()
                ->first()?->school;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('direction')
                    ->label(__('Direction'))
                    ->badge()
                    ->color(fn (PlatformMessage $record): string => $record->isToPlatform() ? 'info' : 'success')
                    ->formatStateUsing(fn (PlatformMessage $record): string => $record->isToPlatform() ? 'Inbox' : 'Outbox'),
                Tables\Columns\TextColumn::make('thread_school')
                    ->label(__('Tenant'))
                    ->getStateUsing(fn (PlatformMessage $record): string => self::resolveThreadSchool($record)?->name ?? __('All tenants')),
                Tables\Columns\TextColumn::make('sender_label')
                    ->label(__('Last message from')),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('thread_size')
                    ->label(__('Messages'))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'important' => 'danger',
                        'info' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Last activity'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('recipient_type')
                    ->label(__('Direction'))
                    ->options([
                        'platform' => __('Inbox (from tenants)'),
                        'school' => __('Outbox (to tenants)'),
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'normal' => __('Normal'),
                        'info' => __('Info'),
                        'important' => __('Important'),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('compose')
                    ->label(__('New Message'))
                    ->icon('heroicon-o-paper-airplane')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Send Message to Tenants'))
                    ->form([
                        Forms\Components\Select::make('audience')
                            ->label(__('Audience'))
                            ->options([
                                'all' => __('All tenants'),
                                'selected' => __('Tenants matching criteria'),
                                'single' => __('A specific tenant'),
                            ])
                            ->default('all')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('school_id')
                            ->label(__('Tenant'))
                            ->options(fn () => School::query()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('audience') === 'single')
                            ->required(fn (Get $get): bool => $get('audience') === 'single'),
                        Forms\Components\Select::make('status_filter')
                            ->label(__('Status is one of'))
                            ->options([
                                'pending' => __('Pending'),
                                'active' => __('Active'),
                                'suspended' => __('Suspended'),
                            ])
                            ->multiple()
                            ->visible(fn (Get $get): bool => $get('audience') === 'selected'),
                        Forms\Components\Select::make('plan_filter')
                            ->label(__('Subscription plan'))
                            ->options(fn () => SaaSPlan::query()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('audience') === 'selected'),
                        Forms\Components\Select::make('region_filter')
                            ->label(__('Region'))
                            ->options([
                                'zimbabwe' => 'Zimbabwe',
                                'south_africa' => 'South Africa',
                                'us' => 'United States',
                            ])
                            ->visible(fn (Get $get): bool => $get('audience') === 'selected'),
                        Forms\Components\TextInput::make('subject')
                            ->label(__('Subject'))
                            ->required()
                            ->maxLength(190),
                        Forms\Components\Textarea::make('body')
                            ->label(__('Message'))
                            ->required()
                            ->rows(6),
                        Forms\Components\Select::make('priority')
                            ->label(__('Priority'))
                            ->options([
                                'normal' => 'Normal',
                                'info' => 'Info',
                                'important' => 'Important',
                            ])
                            ->default('normal'),
                    ])
                    ->action(function (array $data, $action) {
                        $actor = Auth::user();
                        $schoolIds = self::resolveTargetSchools($data);
                        if (empty($schoolIds)) {
                            Notification::make()
                                ->title(__('No matching tenants'))
                                ->body('No tenants matched the selected criteria. Add at least one filter or choose a broader audience.')
                                ->warning()
                                ->send();

                            $action->halt();

                            return;
                        }

                        app(PlatformMessagingService::class)->sendFromPlatform(
                            actor: $actor,
                            subject: $data['subject'],
                            body: $data['body'],
                            priority: $data['priority'] ?? 'normal',
                            scope: $data['audience'],
                            schoolIds: $schoolIds,
                            targetMeta: [
                                'audience' => $data['audience'],
                                'status_filter' => $data['status_filter'] ?? null,
                                'plan_filter' => $data['plan_filter'] ?? null,
                                'region_filter' => $data['region_filter'] ?? null,
                            ],
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_thread')
                    ->label(__('View Thread'))
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->modalHeading(__('Conversation Thread'))
                    ->modalContent(fn (PlatformMessage $record) => view(
                        'filament.admin.resources.platform-message-thread',
                        [
                            'messages' => $record->threadMessages()->get(),
                            'threadParentId' => $record->id,
                            'canReply' => true,
                            'viewerSchoolId' => null,
                        ]
                    ))
                    ->action(function (PlatformMessage $record) {
                        // Mark every tenant-sent message in this thread as read.
                        PlatformMessage::withoutGlobalScopes()
                            ->where('thread_id', $record->thread_id)
                            ->where('recipient_type', 'platform')
                            ->where('is_read', false)
                            ->update(['is_read' => true, 'read_at' => now()]);
                    }),
                Tables\Actions\Action::make('reply')
                    ->label(__('Reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('primary')
                    ->slideOver()
                    ->form([
                        Forms\Components\Placeholder::make('replying_to')
                            ->label(__('Replying to'))
                            ->content(fn (PlatformMessage $record) => (self::resolveThreadSchool($record)?->name ?? __('All tenants')).' — '.($record->subject ?? 'Conversation')),
                        Forms\Components\Textarea::make('body')
                            ->label(__('Reply message'))
                            ->required()
                            ->rows(6),
                    ])
                    ->action(function (PlatformMessage $record, array $data, $action) {
                        $school = self::resolveThreadSchool($record);

                        if (! $school) {
                            Notification::make()
                                ->title(__('No specific tenant'))
                                ->body(__('This is a broadcast conversation. Replies are only possible on conversations with a specific tenant.'))
                                ->warning()
                                ->send();
                            $action->halt();

                            return;
                        }

                        app(PlatformMessagingService::class)->replyFromPlatform(Auth::user(), $record, $data['body']);
                    }),
                Tables\Actions\Action::make('mark_read')
                    ->label(__('Mark as read'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (?PlatformMessage $record): bool => ($record?->isToPlatform() ?? false))
                    ->action(function (PlatformMessage $record) {
                        PlatformMessage::withoutGlobalScopes()
                            ->where('thread_id', $record->thread_id)
                            ->where('recipient_type', 'platform')
                            ->where('is_read', false)
                            ->update(['is_read' => true, 'read_at' => now()]);
                    }),
            ]);
    }

    protected static function resolveTargetSchools(array $data): array
    {
        return match ($data['audience']) {
            'single' => [(int) ($data['school_id'] ?? 0)],
            'all' => School::query()->pluck('id')->all(),
            default => self::resolveFilteredSchools($data),
        };
    }

    protected static function resolveFilteredSchools(array $data): array
    {
        $query = School::query();

        $hasAnyFilter = false;

        if (! empty($data['status_filter'])) {
            $query->whereIn('status', $data['status_filter']);
            $hasAnyFilter = true;
        }

        if (! empty($data['plan_filter'])) {
            $query->whereHas('saasSubscription', fn ($q) => $q->where('saas_plan_id', $data['plan_filter']));
            $hasAnyFilter = true;
        }

        if (! empty($data['region_filter'])) {
            $query->where('region', $data['region_filter']);
            $hasAnyFilter = true;
        }

        // Guard against an accidental broadcast when "selected" was picked with no criteria.
        if (! $hasAnyFilter) {
            return [];
        }

        return $query->pluck('id')->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformMessages::route('/'),
        ];
    }
}
