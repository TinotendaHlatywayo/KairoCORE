<?php

namespace App\Filament\App\Resources\CardTemplateResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\CardTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Modules\Students\Models\CardTemplate;
use Modules\Students\Models\Student;

class ListCardTemplates extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = CardTemplateResource::class;

    protected static ?string $title = 'ID Card Templates';

    protected array $extraBodyAttributes = ['data-page' => 'card-templates'];

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getHeader(): ?View
    {
        return view('filament.app.resources.card-template.index-header', [
            'actions' => $this->getCachedHeaderActions(),
            'templateCount' => CardTemplate::query()->count(),
        ]);
    }

    /**
     * A real learner is used for every card preview so the gallery exercises
     * the same renderer, photos and enrollment data as the print pipeline.
     */
    public function sampleStudentForPreview(): ?Student
    {
        $query = Student::query()
            ->with(['currentEnrollment.course', 'currentEnrollment.section', 'user'])
            ->orderBy('id');

        return (clone $query)->where('status', 'active')->first()
            ?? $query->first();
    }

    public function getHeading(): string
    {
        return __('ID Card Templates');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            Actions\Action::make('bulkPrint')
                ->label(__('Bulk Print ID Cards'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('students.print-cards', [
                    'tenant' => current_tenant()?->subdomain,
                    'scope' => 'all',
                    'layout' => 'pvc',
                ]))
                ->openUrlInNewTab(),
            $this->getHelpAction(),
        ]);
    }
}
