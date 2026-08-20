<?php

namespace App\Services\Csv;

use Modules\Library\Models\LibraryAuthor;
use Modules\Library\Models\LibraryBook;
use Modules\Library\Models\LibraryCategory;
use Modules\Library\Models\LibraryFormat;

class LibraryBookCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'title' => [
                'label' => __('Title'),
                'required' => true,
                'guesses' => ['Title', 'Book Title'],
                'example' => 'Things Fall Apart',
            ],
            'subtitle' => [
                'label' => __('Subtitle'),
                'required' => false,
                'guesses' => ['Subtitle'],
                'example' => 'The Complete Novel',
            ],
            'category' => [
                'label' => __('Category'),
                'required' => true,
                'guesses' => ['Category', 'Library Category'],
                'example' => 'Fiction',
            ],
            'format' => [
                'label' => __('Format'),
                'required' => false,
                'guesses' => ['Format'],
                'example' => 'Paperback',
            ],
            'authors' => [
                'label' => __('Authors'),
                'required' => false,
                'guesses' => ['Author', 'Authors'],
                'example' => 'Chinua Achebe',
            ],
            'publisher' => [
                'label' => __('Publisher'),
                'required' => false,
                'guesses' => ['Publisher'],
                'example' => 'Heinemann',
            ],
            'publication_year' => [
                'label' => __('Publication Year'),
                'required' => false,
                'guesses' => ['Publication Year', 'Year'],
                'example' => '1958',
            ],
            'isbn' => [
                'label' => __('ISBN'),
                'required' => false,
                'guesses' => ['ISBN'],
                'example' => '9780435272463',
            ],
            'language' => [
                'label' => __('Language'),
                'required' => false,
                'guesses' => ['Language'],
                'example' => 'English',
                'default' => 'English',
            ],
            'subject' => [
                'label' => __('Subject'),
                'required' => false,
                'guesses' => ['Subject'],
                'example' => 'African Literature',
            ],
            'grade_level' => [
                'label' => __('Grade Level'),
                'required' => false,
                'guesses' => ['Grade Level'],
                'example' => 'Form 3',
            ],
            'media_type' => [
                'label' => __('Media Type'),
                'required' => false,
                'guesses' => ['Media Type', 'Type'],
                'example' => 'physical',
                'default' => 'physical',
                'in' => ['physical', 'digital'],
            ],
            'external_url' => [
                'label' => __('External URL'),
                'required' => false,
                'guesses' => ['External URL', 'URL'],
                'example' => 'https://example.com/things-fall-apart',
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description'],
                'example' => 'Classic novel of pre-colonial Igbo life.',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Title', 'Subtitle', 'Category', 'Format', 'Authors', 'Publisher',
            'Publication Year', 'ISBN', 'Language', 'Subject', 'Grade Level',
            'Media Type', 'External URL', 'Description',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = LibraryBook::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['category', 'format', 'authors'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $books = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($books->isEmpty()) {
                break;
            }

            foreach ($books as $book) {
                yield [
                    $book->title,
                    $book->subtitle,
                    $book->category?->name,
                    $book->format?->name,
                    $book->authors->pluck('name')->implode(', '),
                    $book->publisher,
                    $book->publication_year,
                    $book->isbn,
                    $book->language,
                    $book->subject,
                    $book->grade_level,
                    $book->media_type,
                    $book->external_url,
                    $book->description,
                ];
            }

            $lastId = $books->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'categories' => LibraryCategory::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (LibraryCategory $c): string => strtolower(trim($c->name))),
            'formats' => LibraryFormat::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (LibraryFormat $f): string => strtolower(trim($f->name)).'|'.$f->media_type),
            'authors' => LibraryAuthor::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn (LibraryAuthor $a): string => strtolower(trim($a->name))),
        ];

        return static::runImport(
            $filePath,
            $schoolId,
            $columnMap,
            $onProgress,
            $lookups,
            fn (array &$data, array $lookups): array => static::validateAndNormalize($data, $lookups),
            function (array $data, int $schoolId, array &$lookups): void {
                static::createRow($data, $schoolId, $lookups);
            },
        );
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        foreach (['title', 'category'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $data['publication_year'] = trim($data['publication_year'] ?? '');
        if ($data['publication_year'] !== '' && ! preg_match('/^\d{4}$/', $data['publication_year'])) {
            $errors[] = 'Publication Year ['.$data['publication_year'].'] must be a 4-digit year (e.g. 1958).';
        }

        $mediaType = strtolower(trim($data['media_type'] ?? ''));
        if ($mediaType === '') {
            $mediaType = 'physical';
        }
        $data['media_type'] = $mediaType;

        if (! in_array($mediaType, ['physical', 'digital'], true)) {
            $errors[] = 'Media Type must be one of: physical, digital.';
        }

        if (empty($errors) && $data['category'] !== '') {
            $category = $lookups['categories'][strtolower($data['category'])] ?? null;

            if (! $category) {
                $errors[] = 'Category ['.$data['category'].'] was not found in this school. Available categories: '.($lookups['categories']->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_category'] = $category;
            }
        }

        $formatName = trim($data['format'] ?? '');
        $data['_format'] = null;

        if ($formatName !== '') {
            if (! in_array($mediaType, ['physical', 'digital'], true)) {
                $errors[] = 'Format ['.$formatName.'] cannot be resolved because the Media Type is invalid.';
            } else {
                $format = $lookups['formats'][strtolower($formatName).'|'.$mediaType] ?? null;

                if (! $format) {
                    $errors[] = 'Format ['.$formatName.'] was not found for media type ['.$mediaType.'] in this school. Available formats: '.($lookups['formats']->pluck('name')->implode(', ') ?: 'none').'.';
                } else {
                    $data['_format'] = $format;
                }
            }
        }

        $data['_authorNames'] = array_values(array_filter(array_map(
            'trim',
            explode(',', $data['authors'] ?? '')
        )));

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $book = LibraryBook::create([
            'school_id' => $schoolId,
            'library_category_id' => $data['_category']->id,
            'library_format_id' => $data['_format']?->id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] !== '' ? $data['subtitle'] : null,
            'publisher' => $data['publisher'] !== '' ? $data['publisher'] : null,
            'publication_year' => $data['publication_year'] !== '' ? $data['publication_year'] : null,
            'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
            'language' => $data['language'] !== '' ? $data['language'] : 'English',
            'subject' => $data['subject'] !== '' ? $data['subject'] : null,
            'grade_level' => $data['grade_level'] !== '' ? $data['grade_level'] : null,
            'media_type' => $data['media_type'],
            'external_url' => $data['external_url'] !== '' ? $data['external_url'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
        ]);

        $authorIds = [];
        foreach ($data['_authorNames'] as $name) {
            $key = strtolower($name);

            if (isset($lookups['authors'][$key])) {
                $authorIds[] = $lookups['authors'][$key]->id;

                continue;
            }

            $author = LibraryAuthor::create(['school_id' => $schoolId, 'name' => $name]);
            $lookups['authors'][$key] = $author;
            $authorIds[] = $author->id;
        }

        if (! empty($authorIds)) {
            $book->authors()->sync($authorIds);
        }
    }
}
