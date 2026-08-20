<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CsvImportService
{
    /**
     * Validate the uploaded CSV headers against expected column names.
     */
    public function validateHeaders(array $uploadedHeaders, array $expectedHeaders): array
    {
        $missing = array_diff($expectedHeaders, $uploadedHeaders);
        $extra = array_diff($uploadedHeaders, $expectedHeaders);

        return [
            'is_valid' => empty($missing),
            'missing' => array_values($missing),
            'extra' => array_values($extra),
        ];
    }

    /**
     * Process a CSV file stream, parse rows, validate individual records, and save in a safe transaction.
     */
    public function import(string $filePath, array $rules, callable $rowCallback): array
    {
        $results = [
            'success_count' => 0,
            'failed_rows' => [],
            'errors' => [],
        ];

        if (! file_exists($filePath) || ! ($handle = fopen($filePath, 'r'))) {
            $results['errors'][] = 'Unable to read the uploaded CSV file.';

            return $results;
        }

        $headers = fgetcsv($handle, 2000, ',');
        if (! $headers) {
            $results['errors'][] = 'The uploaded CSV file is empty.';
            fclose($handle);

            return $results;
        }

        // Clean headers of whitespace or invisible characters
        $headers = array_map('trim', $headers);

        DB::beginTransaction();

        try {
            $rowNumber = 1; // Row 1 is header
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                $rowNumber++;

                // Combine headers with row values
                if (count($headers) !== count($row)) {
                    $results['failed_rows'][] = [
                        'row' => $rowNumber,
                        'errors' => ['Column mismatch: The row contains a different number of fields than the header.'],
                    ];

                    continue;
                }

                $data = array_combine($headers, $row);
                $data = array_map('trim', $data);

                // Run validation rules
                $validator = Validator::make($data, $rules);

                if ($validator->fails()) {
                    $results['failed_rows'][] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->all(),
                    ];

                    continue;
                }

                // Execute the actual insertion logic provided by the caller
                $rowCallback($data);
                $results['success_count']++;
            }

            if (count($results['failed_rows']) > 0) {
                // Roll back database changes if any row failed to maintain clean state
                DB::rollBack();
                $results['errors'][] = 'Import aborted: Some rows contain validation errors. No records were saved.';
            } else {
                DB::commit();
            }

        } catch (Exception $e) {
            DB::rollBack();
            $results['errors'][] = 'Database system failure during transaction processing: '.$e->getMessage();
        } finally {
            fclose($handle);
        }

        return $results;
    }
}
