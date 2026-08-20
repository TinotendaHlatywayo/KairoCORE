<?php

namespace App\Filament\Admin\Resources\SchoolResource\Pages;

use App\Filament\Admin\Resources\SchoolResource;
use App\Models\School;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Onboard New Institution')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Institutions'),
            'pending' => Tab::make('Pending Approval')
                ->badge(School::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'active' => Tab::make('Active')
                ->badge(School::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'suspended' => Tab::make('Suspended')
                ->badge(School::where('status', 'suspended')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended')),
            'trial' => Tab::make('Trial Period')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())),
        ];
    }
}
