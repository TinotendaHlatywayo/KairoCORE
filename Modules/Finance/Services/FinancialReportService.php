<?php

namespace Modules\Finance\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates downloadable official financial statements (PDF, CSV or plain
 * text) for the reporting period shown on the Financial Statements page.
 */
class FinancialReportService
{
    public static function download(string $format, array $data, $startDate, $endDate): StreamedResponse
    {
        return match ($format) {
            'csv' => self::downloadCsv($data, $startDate, $endDate),
            'txt' => self::downloadTxt($data, $startDate, $endDate),
            default => self::downloadPdf($data, $startDate, $endDate),
        };
    }

    protected static function downloadPdf(array $data, $startDate, $endDate): StreamedResponse
    {
        $html = view('finance.financial-statement-pdf', ['data' => $data])->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->loadHtml($html)
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'financial-statement-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    protected static function downloadCsv(array $data, $startDate, $endDate): StreamedResponse
    {
        $rows = [
            [$data['school']],
            [trim($data['companyTagline'] ?? '')],
            [''],
            ['Official Financial Statement & Cash Flow'],
            ['Period: '.$data['startDate'].' to '.$data['endDate']],
            ['Bank Account: '.($data['bankAccountName'] ?? 'All Accounts (Combined)')],
            ['Generated: '.$data['generatedAt']],
            [''],
            ['Description', 'Amount (USD)'],
            ['Total Revenue / Inflows', number_format($data['totalRevenue'], 2)],
            ['Total Refunds', '-'.number_format($data['totalRefunds'], 2)],
            ['Total Expenses & Outflows', '-'.number_format($data['totalExpenses'], 2)],
            ['Staff Salaries', '-'.number_format($data['totalSalaries'] ?? 0, 2)],
            ['Net Cash Flow Balance', number_format($data['netCashFlow'], 2)],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'financial-statement-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    protected static function downloadTxt(array $data, $startDate, $endDate): StreamedResponse
    {
        $lines = implode(PHP_EOL, [
            $data['school'],
            trim($data['companyTagline'] ?? ''),
            '',
            'Official Financial Statement & Cash Flow',
            'Period: '.$data['startDate'].' to '.$data['endDate'],
            'Bank Account: '.($data['bankAccountName'] ?? 'All Accounts (Combined)'),
            'Generated: '.$data['generatedAt'],
            '',
            'Description                      Amount (USD)',
            'Total Revenue / Inflows          '.number_format($data['totalRevenue'], 2),
            'Total Refunds                    -'.number_format($data['totalRefunds'], 2),
            'Total Expenses & Outflows        -'.number_format($data['totalExpenses'], 2),
            'Staff Salaries                  -'.number_format($data['totalSalaries'] ?? 0, 2),
            'Net Cash Flow Balance            '.number_format($data['netCashFlow'], 2),
        ]);

        return response()->streamDownload(
            fn () => print ($lines),
            'financial-statement-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.txt',
            ['Content-Type' => 'text/plain']
        );
    }
}
