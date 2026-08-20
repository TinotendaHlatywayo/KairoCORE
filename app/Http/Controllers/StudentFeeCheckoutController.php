<?php

namespace App\Http\Controllers;

use App\Filament\Student\Resources\HomeworkResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\SaaS\Gateways\GatewayPayload;
use Modules\SaaS\Services\GatewayResolver;

class StudentFeeCheckoutController extends Controller
{
    public function show(Request $request, int $invoiceId)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('filament.student.auth.login');
        }

        $student = HomeworkResource::currentStudent();
        if (! $student) {
            return redirect('/student/my-fees');
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('student_id', $student->id)
            ->first();

        if (! $invoice || (float) $invoice->balance_amount <= 0) {
            return redirect('/student/my-fees');
        }

        $bankAccounts = SchoolBankAccount::where('school_id', $student->school_id)
            ->where('is_active', true)
            ->get();

        return view('modules.finance.student-fee-checkout', [
            'student' => $student,
            'invoice' => $invoice,
            'bankAccounts' => $bankAccounts,
            'error' => session('error'),
        ]);
    }

    public function process(Request $request, int $invoiceId)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('filament.student.auth.login');
        }

        $student = HomeworkResource::currentStudent();
        if (! $student) {
            return redirect('/student/my-fees');
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('student_id', $student->id)
            ->first();

        if (! $invoice || (float) $invoice->balance_amount <= 0) {
            return redirect('/student/my-fees');
        }

        $request->validate([
            'payment_method' => 'required|string|in:ecocash,one_money,omari,zimswitch,visa,mastercard',
            'mobile_number' => 'required|string|max:20',
        ]);

        try {
            $successUrl = route('student.paynow.sandbox-complete');
            $resultUrl = route('saas.paynow.webhook');

            $gateway = app(GatewayResolver::class)->resolve('paynow', [
                'return_url' => $successUrl,
                'result_url' => $resultUrl,
            ]);

            $paymentMethodName = match ($request->input('payment_method')) {
                'ecocash' => 'EcoCash',
                'one_money' => 'OneMoney',
                'omari' => "O'mari",
                'zimswitch' => 'Zimswitch',
                'visa' => 'Visa',
                'mastercard' => 'Mastercard',
                default => 'Paynow',
            };

            $payload = new GatewayPayload(
                amount: (float) $invoice->balance_amount,
                currency: 'USD',
                invoiceNumber: $invoice->invoice_number,
                successUrl: $successUrl,
                cancelUrl: route('filament.student.pages.my-fees'),
                metaData: [
                    'email' => $user->email,
                    'mobile' => $request->input('mobile_number'),
                    'payment_method' => $request->input('payment_method'),
                    'description' => "School Fee Payment ({$paymentMethodName}): {$invoice->invoice_number}",
                    'result_url' => $resultUrl,
                ]
            );

            $response = $gateway->initializePayment($payload);

            if ($response->isSuccess && $response->redirectUrl) {
                StudentPaymentSubmission::create([
                    'school_id' => $invoice->school_id,
                    'invoice_id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'gateway' => 'paynow',
                    'amount' => $invoice->balance_amount,
                    'currency' => 'USD',
                    'status' => StudentPaymentSubmission::STATUS_PENDING,
                    'transaction_reference' => $response->transactionReference,
                ]);

                return redirect($response->redirectUrl);
            }

            return redirect()->route('student.fee-checkout', $invoice->id)
                ->with('error', $response->errorMessage ?: 'Payment initialization failed. Please try again.');

        } catch (\Throwable $e) {
            return redirect()->route('student.fee-checkout', $invoice->id)
                ->with('error', 'Connection error: '.$e->getMessage());
        }
    }
}
