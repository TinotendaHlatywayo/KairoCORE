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
            'xlsx' => FinancialStatementExcelService::download($data, $startDate, $endDate),
            default => self::downloadPdf($data, $startDate, $endDate),
        };
    }

    protected static function downloadPdf(array $data, $startDate, $endDate): StreamedResponse
    {
        $html = view('finance.financial-statement-pdf', ['data' => $data])->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->loadHtml($html)
            ->setPaper('a4', 'portrait');

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
            ['Opening Bank Balance', number_format($data['openingBalance'] ?? 0, 2)],
            ['Total Fees Collected (school fees in period)', '+'.number_format($data['feeRevenue'] ?? 0, 2)],
        ];

        foreach ($data['revenueStreams'] ?? [] as $stream) {
            $rows[] = ['  - '.$stream['name'].' ('.$stream['category'].')'.($stream['date'] ? ' — '.$stream['date'] : ''), '+'.number_format($stream['amount'], 2)];
        }

        $rows[] = ['Less Refunds Issued', '-'.number_format($data['totalRefunds'], 2)];

        foreach ($data['refundItems'] ?? [] as $refund) {
            $rows[] = ['  - '.($refund['reference'] ?? 'Refund').($refund['date'] ? ' — '.$refund['date'] : ''), '-'.number_format($refund['amount'], 2)];
        }

        $rows[] = ['Total Revenue / Inflows (Net of Refunds)', '+'.number_format($data['totalRevenue'], 2)];

        $rows[] = ['Total Expenses & Outflows', '-'.number_format($data['totalExpenses'], 2)];

        foreach ($data['expenseItems'] ?? [] as $expense) {
            $rows[] = ['  - '.$expense['name'].' ('.$expense['category'].')'.($expense['date'] ? ' — '.$expense['date'] : ''), '-'.number_format($expense['amount'], 2)];
        }

        $rows[] = ['  Of which: Staff Salaries', '-'.number_format($data['totalSalaries'] ?? 0, 2)];
        $rows[] = ['Net Cash Flow Balance', number_format($data['netCashFlow'], 2)];
        $rows[] = ['Closing Balance', number_format($data['closingBalance'] ?? (($data['openingBalance'] ?? 0) + $data['netCashFlow']), 2)];

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
        $lines = [
            $data['school'],
            trim($data['companyTagline'] ?? ''),
            '',
            'Official Financial Statement & Cash Flow',
            'Period: '.$data['startDate'].' to '.$data['endDate'],
            'Bank Account: '.($data['bankAccountName'] ?? 'All Accounts (Combined)'),
            'Generated: '.$data['generatedAt'],
            '',
            'Description                                    Amount (USD)',
            'Opening Bank Balance                           '.number_format($data['openingBalance'] ?? 0, 2),
            'Total Fees Collected (school fees in period)  +'.number_format($data['feeRevenue'] ?? 0, 2),
        ];

        foreach ($data['revenueStreams'] ?? [] as $stream) {
            $lines[] = '  - '.$stream['name'].' ('.$stream['category'].')'.($stream['date'] ? ' — '.$stream['date'] : '').'  +'.number_format($stream['amount'], 2);
        }

        $lines[] = 'Less Refunds Issued                            -'.number_format($data['totalRefunds'], 2);

        foreach ($data['refundItems'] ?? [] as $refund) {
            $lines[] = '  - '.($refund['reference'] ?? 'Refund').($refund['date'] ? ' — '.$refund['date'] : '').'  -'.number_format($refund['amount'], 2);
        }

        $lines[] = 'Total Revenue / Inflows (Net of Refunds)        +'.number_format($data['totalRevenue'], 2);

        $lines[] = 'Total Expenses & Outflows                      -'.number_format($data['totalExpenses'], 2);

        foreach ($data['expenseItems'] ?? [] as $expense) {
            $lines[] = '  - '.$expense['name'].' ('.$expense['category'].')'.($expense['date'] ? ' — '.$expense['date'] : '').'  -'.number_format($expense['amount'], 2);
        }

        $lines[] = '  Of which: Staff Salaries                    -'.number_format($data['totalSalaries'] ?? 0, 2);
        $lines[] = 'Net Cash Flow Balance                           '.number_format($data['netCashFlow'], 2);
        $lines[] = 'Closing Balance                                 '.number_format($data['closingBalance'] ?? (($data['openingBalance'] ?? 0) + $data['netCashFlow']), 2);

        return response()->streamDownload(
            fn () => print (implode(PHP_EOL, $lines)),
            'financial-statement-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.txt',
            ['Content-Type' => 'text/plain']
        );
    }
}
