<?php

namespace Tests\Feature;

use Tests\TestCase;

class TempXlsxTemplateRoundTripTest extends TestCase
{
    public function test_all_services_generate_consistent_xlsx_templates(): void
    {
        $classes = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path('Services/Csv'))) as $f) {
            if ($f->isFile() && preg_match('/(\w+CsvService)\.php$/', $f->getFilename(), $m)) {
                $classes[] = 'App\\Services\\Csv\\'.$m[1];
            }
        }

        sort($classes);
        $issues = [];

        foreach ($classes as $class) {
            try {
                $bytes = $class::templateXlsx();
                $this->assertGreaterThan(0, strlen($bytes), $class.' produces a non-empty template');

                $tmp = tempnam(sys_get_temp_dir(), 'rt').'.xlsx';
                file_put_contents($tmp, $bytes);

                $transcode = new \ReflectionMethod($class, 'transcodeXlsxToCsv');
                $csvPath = $transcode->invoke(null, $tmp);

                $readHeaders = new \ReflectionMethod($class, 'readCsvHeaders');
                $headers = $readHeaders->invoke(null, $csvPath);

                $columns = $class::columns();
                $expectedCount = count($columns);

                $this->assertSame(
                    $expectedCount,
                    count($headers),
                    $class.' xlsx transcode yields '.count($headers).' headers, expected '.$expectedCount
                );

                $map = $class::guessMapping($headers);
                $missingRequired = collect($columns)
                    ->filter(fn (array $c): bool => (bool) ($c['required'] ?? false))
                    ->filter(fn (array $c, string $k): bool => blank($map[$k] ?? null))
                    ->keys();

                $this->assertTrue(
                    $missingRequired->isEmpty(),
                    $class.' required columns not auto-guessed from its own template: '.$missingRequired->implode(',')
                );

                @unlink($tmp);
                @unlink($csvPath);
            } catch (\Throwable $e) {
                $issues[] = $class.' → '.get_class($e).': '.$e->getMessage();
            }
        }

        $this->assertSame([], $issues, 'template pipeline failures: '.implode('; ', $issues));
    }

    public function test_course_template_xlsx_generates_without_error(): void
    {
        $bytes = \App\Services\Csv\CourseCsvService::templateXlsx();
        $this->assertStringStartsWith("PK\x03\x04", $bytes);
    }
}