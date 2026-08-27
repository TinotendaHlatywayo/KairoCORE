<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Models\User;
use App\Models\Scopes\TenantScope;
use App\Services\ModuleVisibilityManager;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryBookCopy;
use Modules\Library\Models\LibraryIssue;
use Modules\Students\Models\Student;

class IssueBook extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.issue-book';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static ?string $navigationGroup = 'Library';

    protected static ?string $navigationLabel = 'Issue Book';

    protected static ?string $title = 'Issue Physical Resource';

    protected static ?string $slug = 'issue-book';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('library');
    }

    public string $bookSearch = '';

    public ?int $selectedBookId = null;

    public ?int $selectedCopyId = null;

    public string $recipientSearch = '';

    public string $recipientType = 'student';

    public ?int $selectedStudentId = null;

    public ?int $selectedUserId = null;

    public string $dueDate = '';

    public string $notes = '';

    public array $searchResults = [];

    public array $recipientResults = [];

    public static function getNavigationLabel(): string
    {
        return __('Issue Book');
    }

    public function mount(): void
    {
        $this->dueDate = now()->addDays(14)->format('Y-m-d');

        // Deep-link from the Command Center library scan: pre-select the copy
        // whose barcode was clicked so the librarian can issue it immediately.
        $barcode = request()->query('barcode');
        if ($barcode) {
            $copy = LibraryBookCopy::query()
                ->with('book:id,title')
                ->where('barcode', $barcode)
                ->where('status', 'available')
                ->first();

            if ($copy) {
                $this->selectedBookId = $copy->library_book_id;
                $this->selectedCopyId = $copy->id;
                $this->bookSearch = $copy->book?->title ?? $barcode;
            }
        }
    }

    public function updatedBookSearch(): void
    {
        $this->selectedBookId = null;
        $this->selectedCopyId = null;

        if (strlen($this->bookSearch) < 2) {
            $this->searchResults = [];

            return;
        }

        $search = $this->bookSearch;

        $this->searchResults = LibraryBook::query()
            ->where('media_type', 'physical')
            ->with(['format', 'authors'])
            ->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('publication_year', 'like', "%{$search}%")
                    ->orWhereHas('authors', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });
            })
            ->limit(15)
            ->get()
            ->map(fn ($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'authors' => $book->authors->pluck('name')->join(', '),
                'category' => $book->category?->name ?? 'General',
                'format' => $book->format?->name ?? '',
                'available' => $book->getAvailableCopiesCount(),
                'total' => $book->getTotalCopiesCount(),
            ])
            ->toArray();
    }

    public function selectBook(int $bookId): void
    {
        $book = LibraryBook::find($bookId);
        if (! $book) {
            return;
        }

        $this->selectedBookId = $bookId;
        $this->selectedCopyId = null;
        $this->searchResults = [];
        $this->bookSearch = $book->title;
    }

    public function clearBookSelection(): void
    {
        $this->selectedBookId = null;
        $this->selectedCopyId = null;
        $this->bookSearch = '';
    }

    public function getAvailableCopiesProperty()
    {
        if (! $this->selectedBookId) {
            return collect();
        }

        return LibraryBookCopy::where('library_book_id', $this->selectedBookId)
            ->where('status', 'available')
            ->orderBy('barcode')
            ->get();
    }

    public function updatedRecipientSearch(): void
    {
        $this->selectedStudentId = null;
        $this->selectedUserId = null;

        if (strlen($this->recipientSearch) < 2) {
            $this->recipientResults = [];

            return;
        }

        $search = $this->recipientSearch;

        if ($this->recipientType === 'student') {
            $this->recipientResults = Student::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%")
                        ->orWhere('student_id_number', 'like', "%{$search}%");
                })
                ->limit(15)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => trim($s->first_name.' '.$s->last_name),
                    'number' => $s->admission_number ?? '',
                ])
                ->toArray();
        } else {
            $this->recipientResults = User::query()
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->limit(15)
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email ?? '',
                ])
                ->toArray();
        }
    }

    public function selectStudentRecipient(int $studentId): void
    {
        $student = Student::withoutGlobalScope(TenantScope::class)->find($studentId);
        if (! $student) {
            return;
        }

        $this->selectedStudentId = $studentId;
        $this->selectedUserId = null;
        $this->recipientSearch = trim($student->first_name.' '.$student->last_name).' ('.($student->admission_number ?? '').')';
        $this->recipientResults = [];
    }

    public function selectStaffRecipient(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $this->selectedUserId = $userId;
        $this->selectedStudentId = null;
        $this->recipientSearch = $user->name.' ('.($user->email ?? '').')';
        $this->recipientResults = [];
    }

    public function switchRecipientType(string $type): void
    {
        $this->recipientType = $type;
        $this->recipientSearch = '';
        $this->selectedStudentId = null;
        $this->selectedUserId = null;
        $this->recipientResults = [];
    }

    public function clearRecipientSelection(): void
    {
        $this->recipientSearch = '';
        $this->selectedStudentId = null;
        $this->selectedUserId = null;
    }

    public function submit(): void
    {
        if (! $this->selectedBookId) {
            Notification::make()
                ->title(__('Select a Book'))
                ->body(__('Please search and select a book first.'))
                ->warning()
                ->send();

            return;
        }

        if (! $this->selectedCopyId) {
            Notification::make()
                ->title(__('Select a Copy'))
                ->body(__('Please select an available copy.'))
                ->warning()
                ->send();

            return;
        }

        if (! $this->selectedStudentId && ! $this->selectedUserId) {
            Notification::make()
                ->title(__('Select a Recipient'))
                ->body(__('Please search and select a student or staff member.'))
                ->warning()
                ->send();

            return;
        }

        if (empty($this->dueDate)) {
            Notification::make()
                ->title(__('Set Return Date'))
                ->body(__('Please select a return due date.'))
                ->warning()
                ->send();

            return;
        }

        $copy = LibraryBookCopy::find($this->selectedCopyId);
        if (! $copy || $copy->status !== 'available') {
            Notification::make()
                ->title(__('Copy Unavailable'))
                ->body(__('This copy is no longer available. Please select another.'))
                ->danger()
                ->send();

            return;
        }

        $issue = LibraryIssue::create([
            'school_id' => current_tenant()?->id,
            'library_book_copy_id' => $this->selectedCopyId,
            'student_id' => $this->selectedStudentId,
            'user_id' => $this->selectedUserId,
            'issued_by_id' => auth()->id(),
            'issued_at' => now(),
            'due_at' => $this->dueDate,
            'status' => 'issued',
            'notes' => $this->notes,
        ]);

        Notification::make()
            ->title(__('Book Issued'))
            ->body(__('Resource has been issued successfully. Issue #: ').$issue->id)
            ->success()
            ->send();

        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->bookSearch = '';
        $this->selectedBookId = null;
        $this->selectedCopyId = null;
        $this->recipientSearch = '';
        $this->selectedStudentId = null;
        $this->selectedUserId = null;
        $this->dueDate = now()->addDays(14)->format('Y-m-d');
        $this->notes = '';
        $this->searchResults = [];
        $this->recipientResults = [];
    }

    protected function getViewData(): array
    {
        return [];
    }
}
