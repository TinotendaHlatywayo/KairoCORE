<?php

namespace App\Services\Csv;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Supplier;

class SupplierCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Supplier Name'),
                'required' => true,
                'guesses' => ['Supplier Name', 'Name', 'Vendor Name'],
                'example' => 'Gonville Stationers (Pvt) Ltd',
            ],
            'contact_person' => [
                'label' => __('Contact Person'),
                'required' => false,
                'guesses' => ['Contact Person', 'Contact', 'Representative'],
                'example' => 'Tendai Mutasa',
            ],
            'phone' => [
                'label' => __('Phone'),
                'required' => false,
                'guesses' => ['Phone', 'Phone Number', 'Telephone', 'Mobile'],
                'example' => '+263 772 111 222',
            ],
            'email' => [
                'label' => __('Email'),
                'required' => false,
                'guesses' => ['Email', 'Email Address'],
                'example' => 'orders@gonville.co.zw',
            ],
            'address' => [
                'label' => __('Address'),
                'required' => false,
                'guesses' => ['Address', 'Physical Address'],
                'example' => '45 Nelson Mandela Ave, Harare',
            ],
            'tax_number' => [
                'label' => __('Tax Number'),
                'required' => false,
                'guesses' => ['Tax Number', 'VAT Number', 'Tax ID'],
                'example' => 'TW-55678-20',
            ],
            'opening_balance' => [
                'label' => __('Opening Balance'),
                'required' => false,
                'guesses' => ['Opening Balance', 'Balance'],
                'example' => '0.00',
                'default' => '0',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Supplier Name', 'Contact Person', 'Phone', 'Email', 'Address',
            'Tax Number', 'Opening Balance',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Supplier::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $suppliers = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($suppliers->isEmpty()) {
                break;
            }

            foreach ($suppliers as $supplier) {
                yield [
                    $supplier->name,
                    $supplier->contact_person,
                    $supplier->phone,
                    $supplier->email,
                    $supplier->address,
                    $supplier->tax_number,
                    $supplier->opening_balance,
                ];
            }

            $lastId = $suppliers->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The CSV file has no readable header row. Download the template and use its exact column names.');
        }

        $headerIndex = [];
        foreach ($csvHeaders as $i => $header) {
            $headerIndex[strtolower($header)] = $i;
        }

        $mappedIndexes = [];
        foreach ($columnMap as $key => $header) {
            if (blank($header)) {
                $mappedIndexes[$key] = null;

                continue;
            }
            $mappedIndexes[$key] = $headerIndex[strtolower($header)] ?? null;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the CSV file.');
        }

        fgets($handle);

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle);

        $existingNames = Supplier::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $success = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys(static::columns()), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $existingNames);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingNames, $data, $schoolId) {
                    Supplier::create([
                        'school_id' => $schoolId,
                        'name' => $data['name'],
                        'contact_person' => $data['contact_person'] !== '' ? $data['contact_person'] : null,
                        'phone' => $data['phone'] !== '' ? $data['phone'] : null,
                        'email' => $data['email'] !== '' ? $data['email'] : null,
                        'address' => $data['address'] !== '' ? $data['address'] : null,
                        'tax_number' => $data['tax_number'] !== '' ? $data['tax_number'] : null,
                        'opening_balance' => $data['opening_balance'] !== '' ? (float) $data['opening_balance'] : 0,
                    ]);

                    $existingNames[strtolower(trim($data['name']))] = true;
                });

                $success++;
                $onProgress !== null && $onProgress($processed, $total, false, []);
            } catch (\Throwable $e) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => ['Unexpected database error while saving: '.$e->getMessage()],
                    'data' => $data,
                ];
                $onProgress !== null && $onProgress($processed, $total, true, ['Unexpected database error']);
            }
        }

        fclose($handle);

        return compact('success', 'total', 'failures');
    }

    protected static function validateAndNormalize(array &$data, Collection $existingNames): array
    {
        $errors = [];

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Supplier Name is required (column empty or not mapped).';
        }

        $name = strtolower($data['name']);
        if ($name !== '' && isset($existingNames[$name])) {
            $errors[] = 'Supplier ['.$data['name'].'] already exists in this school.';
        }

        $data['email'] = trim($data['email'] ?? '');
        if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Email ['.$data['email'].'] is not a valid email address.';
        }

        $balance = trim($data['opening_balance'] ?? '');
        if ($balance !== '' && ! is_numeric($balance)) {
            $errors[] = 'Opening Balance ['.$balance.'] must be a number.';
        }

        return $errors;
    }
}
