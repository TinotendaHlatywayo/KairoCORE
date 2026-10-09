<?php

namespace App\Filament\Admin\Resources\SchoolSubscriptionResource\Pages;

use App\Filament\Admin\Resources\SchoolSubscriptionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\SaaS\Services\BillingService;

class ListSchoolSubscriptions extends ListRecords
{
    protected static string $resource = SchoolSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_all_invoices')
                ->label(__('Generate invoices for all schools'))
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('Generate invoices for all schools'))
                ->modalDescription(__('Raises the upcoming invoice for every subscription that has reached its billing date and has no open invoice. Schools already invoiced are skipped.'))
                ->modalSubmitActionLabel(__('Generate invoices'))
                ->action(function () {
                    $summary = app(BillingService::class)->generateInvoicesForAllSchools();

                    Notification::make()
                        ->title(__('Invoices generated'))
                        ->body(__(':generated generated, :skipped skipped, :failed failed.', [
                            'generated' => $summary['generated'],
                            'skipped' => $summary['skipped'],
                            'failed' => $summary['failed'],
                        ]))
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
