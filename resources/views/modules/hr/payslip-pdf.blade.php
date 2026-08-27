<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Remuneration Statement') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .school-info {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .payslip-title {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .metadata-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
        }
        .bold {
            font-weight: bold;
            color: #334155;
        }
        .ledger-container {
            width: 100%;
            margin-bottom: 20px;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 8px 10px;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .ledger-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-box {
            float: right;
            width: 280px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            margin-top: 10px;
            margin-bottom: 30px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        .net-pay-row {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
        }
        .footer-table {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }
        .signature-block {
            border-top: 1px dashed #94a3b8;
            width: 180px;
            text-align: center;
            padding-top: 5px;
            font-size: 9px;
            color: #475569;
        }
        .audit-hash {
            text-align: center;
            margin-top: 40px;
            font-size: 8px;
            color: #94a3b8;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Info -->
        <table class="header-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="school-info">{{ __('Kairo CORE') }}</div>
                    <div style="color: #475569; margin-top: 4px;">{{ __('Official Remuneration Statement') }}</div>
                    <div style="color: #64748b; margin-top: 2px;">Email: financial@schoolcore.test | Tel: +263 242 123456</div>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top;">
                    <div class="payslip-title">{{ __('Payment Advice') }}</div>
                    <div style="margin-top: 4px;">{{ __('Pay Period:') }} <span class="bold">{{ $payslip->run->period->name }}</span></div>
                    <div style="color: #16a34a; font-weight: bold; margin-top: 2px;">{{ __('RELEASED') }}</div>
                </td>
            </tr>
        </table>

        <!-- Employee Metadata Card -->
        <table class="metadata-table">
            <tr>
                <td class="bold" style="width: 20%;">{{ __('Employee Number') }}</td>
                <td style="width: 30%;">{{ $payslip->employee->employee_number }}</td>
                <td class="bold" style="width: 20%;">{{ __('Salary Grade') }}</td>
                <td style="width: 30%;">{{ $payslip->employee->currentGrade->name }}</td>
            </tr>
            <tr>
                <td class="bold">{{ __('Full Name') }}</td>
                <td>{{ $payslip->employee->first_name }} {{ $payslip->employee->last_name }}</td>
                <td class="bold">{{ __('Job Title') }}</td>
                <td>{{ $payslip->employee->designation }}</td>
            </tr>
            <tr>
                <td class="bold">{{ __('Department') }}</td>
                <td>{{ $payslip->employee->department }}</td>
                <td class="bold">{{ __('Payment Method') }}</td>
                <td>{{ $payslip->payment_method }}</td>
            </tr>
        </table>

        <!-- Ledger Splits (Earnings vs Deductions) -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <!-- Left: Earnings Ledger -->
                <td style="width: 48%; vertical-align: top; padding-right: 15px;">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th colspan="2">EARNINGS (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payslip->items->where('type', 'earning') as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td style="text-align: right;" class="bold">${{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <!-- Right: Deductions Ledger -->
                <td style="width: 48%; vertical-align: top; padding-left: 15px;">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th colspan="2">DEDUCTIONS (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payslip->items->where('type', 'deduction') as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td style="text-align: right;" class="bold" style="color: #dc2626;">-${{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Totals & Balances -->
        <div class="summary-box">
            <table>
                <tr>
                    <td>{{ __('Total Gross Earnings') }}</td>
                    <td style="text-align: right;" class="bold">${{ number_format($payslip->gross_pay, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Total Deductions') }}</td>
                    <td style="text-align: right;" class="bold" style="color: #dc2626;">-${{ number_format($payslip->total_deductions, 2) }}</td>
                </tr>
                <tr class="net-pay-row">
                    <td>{{ __('NET REMUNERATION DUE') }}</td>
                    <td style="text-align: right;">${{ number_format($payslip->net_pay, 2) }}</td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>

        <!-- Footer Sign-offs -->
        <table class="footer-table">
            <tr>
                <td style="width: 30%;">
                    <!-- Security Stamp Placeholder -->
                    <div style="border: 1px solid #cbd5e1; width: 80px; height: 80px; padding: 5px; text-align: center; font-size: 8px; color: #64748b;">
                        {{ __('SECURE PAYSLIP') }}<br>
                        {{ __('VERIFICATION') }}<br>
                        <div style="background-color: #e2e8f0; height: 35px; margin-top: 5px; border: 1px dashed #94a3b8;"></div>
                    </div>
                </td>
                <td style="width: 70%; text-align: right; vertical-align: bottom;">
                    <table style="float: right;">
                        <tr>
                            <td style="padding-right: 30px;">
                                <div class="signature-block">{{ __('Preparing Officer') }}</div>
                            </td>
                            <td>
                                <div class="signature-block">{{ __('Authorizing Stamp') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Security Hash Checksum -->
        <div class="audit-hash">
            Cryptographic Checksum Signature: {{ $payslip->integrity_hash }}
        </div>
    </div>
</body>
</html>