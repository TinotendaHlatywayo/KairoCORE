<?php

namespace App\Filament\Admin\Resources\SchoolResource\Pages;

use App\Filament\Admin\Resources\SchoolResource;
use App\Services\SchoolApprovalService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label(__('Approve & Send Activation'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record !== null && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;

                    $result = app(SchoolApprovalService::class)->approve($record);
                    $adminUser = $result['admin_user'];
                    $token = $result['token'];

                    if (! $adminUser || ! $token) {
                        Notification::make()
                            ->title(__('Institution Approved'))
                            ->body(__('No pending administrator was found, or the activation email could not be delivered. Use "Resend Activation" once the record is ready.'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $seedingNote = $record->has_dummy_data
                        ? __(' Demo data is now generating in the background and will appear once ready.')
                        : '';

                    Notification::make()
                        ->title(__('Institution Approved & Activation Email Sent'))
                        ->body("Activation link sent to {$adminUser->email}.{$seedingNote}")
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}