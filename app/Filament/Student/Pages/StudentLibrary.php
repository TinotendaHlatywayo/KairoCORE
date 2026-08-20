<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Livewire\WithPagination;
use Modules\Knowledge\Models\KnowledgeAsset;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryCategory;

class StudentLibrary extends Page
{
    use WithPagination;

    protected static string $view = 'filament.student.pages.student-library';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Library';

    protected static ?string $navigationLabel = 'Library';

    protected static ?string $title = 'Library & Resources';

    protected static ?string $slug = 'library';

    public string $search = '';

    public string $categoryFilter = '';

    public string $mediaFilter = '';

    public string $tab = 'books';

    public int $perPage = 24;

    public static function getNavigationLabel(): string
    {
        return __('Library');
    }

    public function mount(): void {}

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMediaFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function getCategoriesProperty()
    {
        return LibraryCategory::query()
            ->withCount('books')
            ->orderBy('name')
            ->get();
    }

    public function getBooksProperty()
    {
        $query = LibraryBook::query()
            ->with(['category', 'format', 'authors'])
            ->orderBy('title');

        if (filled($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhereHas('authors', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->categoryFilter)) {
            $query->whereHas('category', function ($q) {
                $q->where('name', $this->categoryFilter);
            });
        }

        if (filled($this->mediaFilter)) {
            $query->where('media_type', $this->mediaFilter);
        }

        return $query->paginate($this->perPage);
    }

    public function getKnowledgeAssetsProperty()
    {
        $query = KnowledgeAsset::query()
            ->with(['category', 'format', 'authors'])
            ->where('visibility', '!=', 'private')
            ->orderBy('title');

        if (filled($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('abstract_description', 'like', "%{$search}%")
                    ->orWhereHas('authors', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($this->categoryFilter)) {
            $query->whereHas('category', function ($q) {
                $q->where('name', $this->categoryFilter);
            });
        }

        if (filled($this->mediaFilter)) {
            $query->where('media_type', $this->mediaFilter);
        }

        return $query->paginate($this->perPage);
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        return [
            'student' => $student,
            'categories' => $this->categories,
        ];
    }
}
