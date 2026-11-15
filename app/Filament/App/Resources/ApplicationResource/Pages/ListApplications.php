<?php

namespace App\Filament\App\Resources\ApplicationResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academics\Models\Course;
use Modules\Admissions\Models\Application;

class ListApplications extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = ApplicationResource::class;

    protected static ?string $title = 'Online Applications';

    protected array $extraBodyAttributes = ['data-page' => 'admissions'];

    public string $statusFilter = '';
    public string $gradeFilter = '';
    public string $applicationView = 'grid';

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

    public function getHeader(): ?View
    {
        return view('filament.app.resources.application.index-header', [
            'actions' => $this->getCachedHeaderActions(),
            'stats' => $this->getApplicationStats(),
        ]);
    }

    /**
     * @return array{total:int,pending:int,review:int,enrolled:int,rejected:int}
     */
    public function getApplicationStats(): array
    {
        $row = Application::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'pending' then 1 else 0 end) as pending")
            ->selectRaw("sum(case when status in ('verified','confirmed') then 1 else 0 end) as review")
            ->selectRaw("sum(case when status = 'enrolled' then 1 else 0 end) as enrolled")
            ->selectRaw("sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'review' => (int) ($row->review ?? 0),
            'enrolled' => (int) ($row->enrolled ?? 0),
            'rejected' => (int) ($row->rejected ?? 0),
        ];
    }

    /** @return array<int,string> */
    public function getGradeOptions(): array
    {
        return Course::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function updatedStatusFilter(): void { $this->deselectAllTableRecords(); $this->resetPage(); }
    public function updatedGradeFilter(): void { $this->deselectAllTableRecords(); $this->resetPage(); }
    public function updatedApplicationView(): void { $this->deselectAllTableRecords(); }

    public function clearApplicationFilters(): void
    {
        $this->tableSearch = '';
        $this->statusFilter = '';
        $this->gradeFilter = '';
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function applyStatFilter(string $filter): void
    {
        $this->statusFilter = match ($filter) {
            'all', '' => '',
            'review' => 'review',
            default => $filter,
        };
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        return $query;
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = $this->applyApplicationFilters(parent::getFilteredTableQuery());

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    protected function applyApplicationFilters(Builder $query): Builder
    {
        $search = trim((string) $this->tableSearch);
        if ($search !== '') {
            $terms = preg_split('/\s+/u', $search) ?: [];
            foreach ($terms as $term) {
                $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
                $query->where(function (Builder $q) use ($term): void {
                    $like = '%'.$term.'%';
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('application_number', 'like', $like)
                        ->orWhere('parent_phone', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            }
        }

        if ($this->gradeFilter !== '') {
            $query->where('course_id', (int) $this->gradeFilter);
        }

        $sf = $this->statusFilter;
        if ($sf === 'review') {
            $query->whereIn('status', ['verified', 'confirmed']);
        } elseif ($sf !== '') {
            $query->where('status', $sf);
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            $this->getHelpAction(),
        ]);
    }
}
