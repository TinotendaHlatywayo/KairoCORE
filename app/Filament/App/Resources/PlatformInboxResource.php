<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PlatformInboxResource\Pages\ListPlatformInboxes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Services\PermissionRegistry;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Models\PlatformMessageRecipient;
use Modules\SaaS\Services\PlatformMessagingService;

class PlatformInboxResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = PlatformMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $navigationLabel = 'SchoolCore Messages';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 99;

    // Reached via the Communication Center contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user || $user->school_id === null) {
            return false;
        }

        return PermissionRegistry::checkPermission('communication.contact_platform');
    }

    public static function table(Table $table): Table
    {
        $schoolId = Auth::user()->school_id;
        $receivedIds = PlatformMessageRecipient::query()
            ->where('school_id', $schoolId)
            ->pluck('message_id');

        return $table
            ->query(
                PlatformMessage::withoutTenantScope()
                    ->where(function ($q) use ($schoolId, $receivedIds) {
                        $q->where(fn ($q2) => $q2->where('sender_type', 'school')->where('school_id', $schoolId))
                            ->orWhere(fn ($q2) => $q2->where('sender_type', 'platform')->whereIn('id', $receivedIds));
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('direction')
                    ->label(__('Direction'))
                    ->badge()
                    ->color(fn (PlatformMessage $record): string => $record->isFromPlatform() ? 'info' : 'success')
                    ->formatStateUsing(fn (PlatformMessage $record): string => $record->isFromPlatform() ? 'Received' : 'Sent'),
                Tables\Columns\TextColumn::make('sender_label')
                    ->label(__('From')),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'important' => 'danger',
                        'info' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('read_indicator')
                    ->label(__('Read'))
                    ->boolean()
                    ->getStateUsing(fn (PlatformMessage $record) => self::isReadByTenant($record)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label(__('Direction'))
                    ->options([
                        'received' => __('Received from SchoolCore'),
                        'sent' => __('Sent to SchoolCore'),
                    ])
                    ->query(fn ($query, array $data) => self::applyDirectionFilter($query, $data, $schoolId, $receivedIds)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('new_message')
                    ->label(__('Send Message to SchoolCore'))
                    ->icon('heroicon-o-paper-airplane')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->form([
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
                                'normal' => __('Normal'),
                                'info' => __('Info'),
                                'important' => __('Important'),
                            ])
                            ->default('normal'),
                    ])
                    ->action(function (array $data) {
                        app(PlatformMessagingService::class)->sendFromSchool(
                            actor: Auth::user(),
                            subject: $data['subject'],
                            body: $data['body'],
                            priority: $data['priority'] ?? 'normal',
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
                        ['messages' => $record->threadMessages()->get()]
                    ))
                    ->action(function (PlatformMessage $record) {
                        self::markReceivedRead($record);
                    }),
                Tables\Actions\Action::make('reply')
                    ->label(__('Reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('primary')
                    ->visible(fn (?PlatformMessage $record): bool => $record?->isFromPlatform() ?? false)
                    ->slideOver()
                    ->form([
                        Forms\Components\Textarea::make('body')
                            ->label(__('Reply message'))
                            ->required()
                            ->rows(6),
                    ])
                    ->action(function (PlatformMessage $record, array $data) {
                        app(PlatformMessagingService::class)->replyFromSchool(Auth::user(), $record, $data['body']);
                    }),
                Tables\Actions\Action::make('mark_read')
                    ->label(__('Mark as read'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (?PlatformMessage $record): bool => $record?->isFromPlatform() ?? false && ! self::isReadByTenant($record))
                    ->action(function (PlatformMessage $record) {
                        self::markReceivedRead($record);
                    }),
            ]);
    }

    protected static function isReadByTenant(PlatformMessage $record): bool
    {
        if (! $record->isFromPlatform()) {
            return $record->is_read;
        }

        $recipient = $record->recipients()
            ->where('school_id', Auth::user()->school_id)
            ->first();

        return $recipient?->status === 'read';
    }

    protected static function markReceivedRead(PlatformMessage $record): void
    {
        $recipient = $record->recipients()
            ->where('school_id', Auth::user()->school_id)
            ->first();

        $recipient?->markRead();
    }

    protected static function applyDirectionFilter($query, array $data, int $schoolId, $receivedIds)
    {
        if (empty($data['value'])) {
            return;
        }

        if ($data['value'] === 'received') {
            $query->where('sender_type', 'platform')->whereIn('id', $receivedIds);
        } else {
            $query->where('sender_type', 'school')->where('school_id', $schoolId);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformInboxes::route('/'),
        ];
    }
}
