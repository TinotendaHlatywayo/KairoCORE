<?php

namespace App\Filament\App\Resources\ApplicationResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListApplications extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = ApplicationResource::class;

    protected static ?string $title = 'Online Applications';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    /**
     * This page renders its own heading inside the card view, so getHeading()
     * returns an empty string to suppress Filament's built-in title. Filament
     * skips the whole header in that case, which also skipped every header
     * action - the Create and Help buttons were never rendered at all. Supplying
     * the header explicitly keeps the toolbar actions on the page.
     */
    public function getHeader(): ?View
    {
        return view('filament.components.header-actions', [
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            $this->getHelpAction(),
        ]);
    }
}
