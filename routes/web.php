<?php

use App\Http\Controllers\AcademicReportPdfController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\FinanceDocumentController;
use App\Http\Controllers\SaaS\InvoiceDownloadController;
use App\Http\Controllers\SaaS\PaynowWebhookController;
use App\Http\Controllers\SaaS\ReceiptDownloadController;
use App\Http\Controllers\StudentCardPrintController;
use App\Http\Controllers\StudentFeeCheckoutController;
use App\Http\Middleware\SetUserLocale;
use App\Livewire\RegistrationWizard;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Academics\Http\Controllers\ReportVerificationController;
use Modules\Academics\Models\Section;
use Modules\CMS\Http\Controllers\CmsRenderController;
use Modules\Finance\Http\Controllers\FinanceDocumentVerificationController;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\Finance\Services\StudentFeePaymentService;
use Modules\Knowledge\Models\KnowledgeAsset;
use Modules\Library\Models\LibraryBook;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\SubscriptionManager;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimeSlot;

// 1. Root / Central Marketing & Registration Routing (lvh.me)
Route::domain(parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    Route::get('/', function () {
        return view('marketing.home');
    })->name('marketing.home');

    Route::get('/robots.txt', function () {
        return response("User-agent: *\nAllow: /\n", 200, ['Content-Type' => 'text/plain']);
    })->name('marketing.robots');

    Route::get('/about', function () {
        return view('marketing.about');
    })->name('marketing.about');

    Route::get('/contact', function () {
        return view('marketing.contact');
    })->name('marketing.contact');

    Route::post('/contact', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Mail::raw(
            "Name: {$data['name']}\nEmail: {$data['email']}\n\n{$data['message']}",
            function ($message) use ($data) {
                $message->to(config('mail.platform.address') ?: config('mail.from.address'))
                    ->replyTo($data['email'], $data['name'])
                    ->subject('Kairo CORE Contact: '.$data['name']);
            }
        );

        return back()->with('success_message', __('Your message has been sent. We will get back to you shortly.'));
    })->name('marketing.contact.submit');

    // School Portal Multi-step Registration
    Route::get('/register', RegistrationWizard::class)
        ->middleware('throttle:rate_limit:registration')
        ->name('register');
    // POST registration is handled via Livewire wire:click="submit" inside the wizard component.
    Route::get('/register/success', function () {
        return view('auth.register-success');
    })->name('registration.success');

    // Secure Account Activation (time-sensitive, single-use token)
    Route::get('/activate-account', [ActivationController::class, 'show'])->name('account.activate');
    Route::post('/activate-account', [ActivationController::class, 'activate'])->name('account.activate.submit');
    Route::get('/activate-account/request-new-link', [ActivationController::class, 'requestForm'])->name('account.activate.request');
    Route::post('/activate-account/request-new-link', [ActivationController::class, 'resend'])
        ->middleware('throttle:5,10')
        ->name('account.activate.resend');

    // Google Single Sign-On
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    // Completes Google SSO on the TENANT subdomain (single-use ticket from the
    // central callback) so the session is created inside this tenant's scope.
    Route::get('/auth/sso/consume', [GoogleAuthController::class, 'consume'])->name('auth.sso.consume');

    // Platform Terms of Service
    Route::get('/terms', function () {
        return view('terms.platform');
    })->name('platform.terms');

    Route::get('/terms/pdf', function () {
        $pdf = Pdf::loadView('terms.platform');
        return $pdf->download('Kairo CORE-Platform-Terms-of-Service.pdf');
    })->name('platform.terms.pdf');
});

// Livewire update endpoint fallback - handles stray GET requests gracefully
// (e.g. prefetches). Bounce the visitor home instead of showing raw JSON.
Route::get('/livewire/update', function () {
    return redirect('/', 302);
})->name('livewire.update.fallback');

// Public language switcher for the platform website. Stores the choice in the
// session so every visitor can browse the marketing site in their language.
Route::get('/locale/{locale}', function (string $locale) {
    $supported = ['en', 'sn', 'sw', 'fr', 'pt', 'es'];

    if (in_array($locale, $supported, true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back()->setStatusCode(302);
})->name('locale.switch');

// 2. Tenant Routing Group (Subdomains and Custom Domains like rujeko.lvh.me)
// SetUserLocale runs after ResolveTenant here so the school's locale is applied
// (it already ran in the web group with session/user context).
Route::domain('{tenant}.'.parse_url(config('app.url'), PHP_URL_HOST))->middleware(['tenant', SetUserLocale::class])->group(function () {

    // School Terms & Conditions
    Route::get('/school-terms', function () {
        $school = app('current_tenant');
        $termsContent = $school ? \Modules\Admin\Models\SystemSetting::get('legal', 'terms_content', default_school_terms()) : default_school_terms();
        return view('terms.school', ['school' => $school, 'termsContent' => $termsContent]);
    })->name('school.terms');

    Route::get('/school-terms/pdf', function () {
        $school = app('current_tenant');
        $termsContent = $school ? \Modules\Admin\Models\SystemSetting::get('legal', 'terms_content', default_school_terms()) : default_school_terms();
        $pdf = Pdf::loadView('terms.school', ['school' => $school, 'termsContent' => $termsContent]);
        return $pdf->download(($school?->name ?? 'School') . '-Terms-and-Conditions.pdf');
    })->name('school.terms.pdf');

    // PUBLIC CUSTOM DYNAMIC WEBSITE ROOT ENTRY POINT [2]
    Route::get('/', [CmsRenderController::class, 'render'])->name('tenant.home');

    // School Portal Public Admission Application — served through the CMS.
    // /apply and /apply-online both render the same CMS application page so the
    // pre-existing /apply URL (header "Apply Online", hero "Apply For Admission"
    // and "Start an application" CTAs) keeps working directly.
    Route::get('/apply', function (Request $request) {
        return app(CmsRenderController::class)->render($request, 'apply-online');
    })->name('tenant.apply');

    // Single Student ID Card Print compilation
    Route::get('/students/{student}/print-id', function (Student $student) {
        if ($student->school_id !== app('current_tenant')->id) {
            abort(403);
        }

        return view('tenant.id-card-templates', [
            'students' => collect([$student]),
            'school' => app('current_tenant'),
        ]);
    })->name('tenant.student.print-id')->middleware(['auth']);

    // Bulk Student ID Cards compiler
    Route::get('/students/print-bulk', function (Request $request) {
        $ids = explode(',', $request->query('ids', ''));

        $students = Student::whereIn('id', $ids)
            ->where('school_id', app('current_tenant')->id)
            ->get();

        if ($students->isEmpty()) {
            return 'No students selected for card printing.';
        }

        return view('tenant.id-card-templates', [
            'students' => $students,
            'school' => app('current_tenant'),
        ]);
    })->name('tenant.student.print-ids-bulk')->middleware(['auth']);

    // Official Class Timetable Print Compiler Route
    Route::get('/classrooms/{section}/print-timetable', function (Section $section) {
        if ($section->school_id !== app('current_tenant')->id) {
            abort(403);
        }

        $school = app('current_tenant');
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $timeSlots = TimeSlot::where('school_id', $school->id)
            ->orderBy('start_time', 'asc')
            ->get();

        return view('tenant.timetable-print', compact('section', 'school', 'days', 'timeSlots'));
    })->name('tenant.timetable.print')->middleware(['auth']);

    // Standard Fallbacks: Redirect old routes directly to the student portal login
    Route::get('/login', function () {
        return redirect('/student/login');
    })->name('login');

    Route::get('/dashboard', function () {
        return redirect('/workspace');
    })->name('dashboard');

    // Public route for QR Code Verification
    Route::get('/verify-report/{hash}', [ReportVerificationController::class, 'verify'])
        ->name('report.verify');

    // Public route for finance document (invoice / receipt / statement) QR verification
    Route::get('/verify-finance/{hash}', [FinanceDocumentVerificationController::class, 'verify'])
        ->name('finance.verify');

    // Secure Student ID Card QR public verification endpoint
    Route::get('/verify-card/{hash}', function ($hash) {
        $student = Student::all()->filter(function ($item) use ($hash) {
            return hash_hmac('sha256', $item->student_id_number, config('app.key')) === $hash;
        })->first();

        if (! $student) {
            abort(404, 'Invalid ID Card Security Token.');
        }

        return view('modules.students.card-verify', [
            'student' => $student,
            'school' => $student->school,
        ]);
    })->name('card.verify');

    // Bulk print ID Cards PDF compiler endpoint
    Route::get('/workspace/students/cards/print', [StudentCardPrintController::class, 'generate'])
        ->middleware(['web', 'auth', 'tenant'])
        ->name('students.print-cards');

    Route::get('/e-resource/view/{id}', function ($id) {
        $book = LibraryBook::withoutGlobalScopes()->findOrFail($id);

        $disk = Storage::disk('public');
        if (! $disk->exists($book->file_path)) {
            abort(404, 'The requested digital resource does not exist.');
        }

        $physicalPath = $disk->path($book->file_path);

        $mimeType = mime_content_type($physicalPath);
        if (! $mimeType) {
            $mimeType = 'application/pdf';
        }

        return response()->file($physicalPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($book->file_path).'"',
        ]);
    })->name('e-resource.view')->middleware(['web']);

    Route::get('/knowledge-asset/view/{id}', function ($id) {
        $asset = KnowledgeAsset::withoutGlobalScopes()->findOrFail($id);

        $disk = Storage::disk('public');
        if (! $disk->exists($asset->file_path)) {
            abort(404, 'The requested document does not exist.');
        }

        $physicalPath = $disk->path($asset->file_path);
        $mimeType = mime_content_type($physicalPath);
        if (! $mimeType) {
            $mimeType = 'application/pdf';
        }

        return response()->file($physicalPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($asset->file_path).'"',
        ]);
    })->name('knowledge-asset.view')->middleware(['web']);

    // =========================================================================
    // VISUAL WEBSITE & CMS BUILDER FRONTLEND ROUTES (Multi-Tenant Scoped)
    // =========================================================================

    // 1. Dynamic sitemap generation
    Route::get('/sitemap.xml', [CmsRenderController::class, 'sitemap'])
        ->name('cms-sitemap');

    // 2. Per-tenant robots.txt
    Route::get('/robots.txt', [CmsRenderController::class, 'robots'])
        ->name('cms-robots');

    // 2. Submit online parent admissions applications via the portal
    Route::post('/cms-render/apply-submit', [CmsRenderController::class, 'submitApplication'])
        ->middleware('throttle:20,10')
        ->name('cms-apply-submit');

    Route::post('/cms-render/contact-submit', [CmsRenderController::class, 'submitContact'])
        ->middleware('throttle:8,1')
        ->name('cms-contact-submit');

    Route::get('/_cms-preview/{slug}', [CmsRenderController::class, 'preview'])
        ->middleware('auth')
        ->name('cms.preview');

    // 3. Fallback Dynamic Page Wildcard Resolver [2]
    // The Filament panel home paths (workspace, student, platform) are
    // reserved for the panels, which are registered AFTER this wildcard.
    // Excluding them here lets the panel dashboard routes win instead of
    // being swallowed by the CMS page resolver (404).
    Route::get('/{slug}', [CmsRenderController::class, 'render'])
        ->where('slug', '^(?!(?:workspace|student|platform)$)[^/]+')
        ->name('cms-render');
});

// =========================================================================
// Document download & PDF streaming routes (outside {tenant} domain group
// so route() generates URLs without needing a {tenant} parameter).
// ResolveTenant middleware resolves the school from the hostname.
// =========================================================================
Route::middleware(['tenant', 'auth', 'throttle:rate_limit:exports'])->group(function () {
    // Reports
    Route::get('/documents/reports/{record}/pdf', [AcademicReportPdfController::class, 'generate'])
        ->name('report.pdf');
    Route::get('/documents/reports/bulk-pdf', [AcademicReportPdfController::class, 'bulkGenerate'])
        ->name('reports.bulk-pdf');

    // Finance documents
    Route::get('/documents/finance/invoices/{record}/pdf', [FinanceDocumentController::class, 'printInvoice'])
        ->name('invoice.pdf');
    Route::get('/documents/finance/receipts/{record}/pdf', [FinanceDocumentController::class, 'printReceipt'])
        ->name('receipt.pdf');
    Route::get('/documents/finance/statements/{record}/pdf', [FinanceDocumentController::class, 'printStatement'])
        ->name('statement.pdf');
    Route::get('/documents/finance/structures/{term}/pdf', [FinanceDocumentController::class, 'printFeeStructure'])
        ->name('structure.pdf');
    Route::get('/documents/finance/bulk-invoices/pdf', [FinanceDocumentController::class, 'bulkGenerate'])
        ->name('invoices.bulk-pdf');

    // Student & application documents
    Route::get('/documents/students/{student}/documents/{document}/download', [DocumentDownloadController::class, 'studentDocument'])
        ->name('student.document.download');
    Route::get('/documents/applications/{application}/documents/{document}/download', [DocumentDownloadController::class, 'applicationDocument'])
        ->name('application.document.download');
    Route::get('/documents/applications/{application}/documents/{document}/view', [DocumentDownloadController::class, 'viewApplicationDocument'])
        ->name('application.document.view');
});

// Central SaaS Invoice & Receipt Download Endpoints
Route::middleware(['auth'])->group(function () {
    Route::get('/saas/invoice/{uuid}/download', [InvoiceDownloadController::class, 'download'])
        ->name('saas.invoice.download');

    Route::get('/saas/receipt/{uuid}/download', [ReceiptDownloadController::class, 'download'])
        ->name('saas.receipt.download');
});

// Paynow Sandbox Simulator Routes
Route::get('/saas/paynow/sandbox-redirect', function (Request $request) {
    $ref = $request->query('ref');
    $amount = $request->query('amount', '50.00');
    $invoiceNumber = $request->query('invoice', 'INV-SAAS');

    return view('modules.saas.paynow-sandbox', compact('ref', 'amount', 'invoiceNumber'));
})->name('saas.paynow.sandbox-redirect')->middleware(['auth']);

Route::post('/saas/paynow/sandbox-complete', function (Request $request) {
    $ref = $request->input('ref');

    $transaction = SaaSTransaction::where('transaction_reference', $ref)->first();
    if ($transaction) {
        $transaction->update(['status' => 'completed', 'processed_at' => now()]);
        app(SubscriptionManager::class)->processTransactionVerification($transaction);
    }

    return redirect('/workspace/saas-billing-overview')
        ->with('filament.notifications', [['title' => 'Payment Successful!', 'body' => 'Your subscription payment via Paynow was processed successfully.', 'status' => 'success']]);
})->name('saas.paynow.sandbox-complete')->middleware(['auth']);

// Paynow SaaS Webhook (No Auth, No CSRF)
Route::post('/saas/paynow/webhook', [PaynowWebhookController::class, 'handle'])
    ->name('saas.paynow.webhook')
    ->withoutMiddleware([ValidateCsrfToken::class]);

// Student Fee Checkout (Paynow pre-checkout page)
Route::get('/student/fee-checkout/{invoiceId}', [StudentFeeCheckoutController::class, 'show'])
    ->name('student.fee-checkout')
    ->middleware(['auth']);
Route::post('/student/fee-checkout/{invoiceId}', [StudentFeeCheckoutController::class, 'process'])
    ->name('student.fee-checkout.process')
    ->middleware(['auth']);

// Student Fee Paynow Sandbox Simulator Routes
Route::get('/student/paynow/sandbox-redirect', function (Request $request) {
    $ref = $request->query('ref');
    $amount = $request->query('amount', '50.00');
    $invoiceNumber = $request->query('invoice', 'INV-FEES');

    return view('modules.finance.student-paynow-sandbox', compact('ref', 'amount', 'invoiceNumber'));
})->name('student.paynow.sandbox-redirect')->middleware(['auth']);

Route::post('/student/paynow/sandbox-complete', function (Request $request) {
    $ref = $request->input('ref');

    $submission = StudentPaymentSubmission::where('transaction_reference', $ref)
        ->orWhere('id', is_numeric($ref) ? (int) $ref : 0)
        ->where('status', StudentPaymentSubmission::STATUS_PENDING)
        ->where('gateway', 'paynow')
        ->first();

    if ($submission) {
        StudentFeePaymentService::creditSubmission($submission);
    }

    return redirect('/student/my-fees')
        ->with('filament.notifications', [['title' => 'Payment Successful!', 'body' => 'Your fee payment via Paynow was processed successfully.', 'status' => 'success']]);
})->name('student.paynow.sandbox-complete')->middleware(['auth']);
