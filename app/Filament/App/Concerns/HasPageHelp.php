<?php

namespace App\Filament\App\Concerns;

use App\Support\HelpContent;
use Filament\Actions\Action;

trait HasPageHelp
{
    protected function getHelpAction(): ?Action
    {
        $resourceClass = method_exists($this, 'getResource') ? $this->getResource() : static::class;
        $help = HelpContent::for($resourceClass);

        if (!$help) {
            return null;
        }

        return Action::make('pageHelp')
            ->label('Help')
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->modalHeading($help['title'])
            ->modalDescription($help['summary'] ?? '')
            ->modalContent(view('filament.components.help-modal', ['help' => $help]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }
}
