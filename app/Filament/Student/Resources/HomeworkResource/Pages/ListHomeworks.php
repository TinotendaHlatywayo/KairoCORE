<?php

namespace App\Filament\Student\Resources\HomeworkResource\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Resources\Pages\ListRecords;

class ListHomeworks extends ListRecords
{
    protected static string $resource = HomeworkResource::class;
}
