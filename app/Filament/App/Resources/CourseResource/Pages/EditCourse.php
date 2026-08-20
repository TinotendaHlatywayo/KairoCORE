<?php

namespace App\Filament\App\Resources\CourseResource\Pages;

use App\Filament\App\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    public function getHeader(): ?View
    {
        return view('filament.app.resources.course-resource.pages.edit-course-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
