<?php

namespace App\Filament\App\Resources\TeacherAssignmentResource\Pages;

use App\Filament\App\Resources\TeacherAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherAssignments extends ListRecords
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('multiClass')
                ->label(__('Assign Subject Specialist'))
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->url(static::getResource()::getUrl('create-multi'))
                ->openUrlInNewTab(false),
            Actions\CreateAction::make()
                ->label(__('Assign Teacher to a Class'))
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
