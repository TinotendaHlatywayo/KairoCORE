<?php

use App\Http\Controllers\AcademicReportPdfController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Communication\CommunicationAttachmentController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\FinanceDocumentController;
use App\Http\Controllers\HelpCentreController;
use App\Http\Controllers\Platform\PlatformBackupDownloadController;
use App\Http\Controllers\Platform\PlatformEntryController;
use App\Http\Controllers\SaaS\DocumentPreviewController;
use App\Http\Controllers\SaaS\InvoiceDownloadController;
use App\Http\Controllers\SaaS\PaynowWebhookController;
use App\Http\Controllers\SaaS\ReceiptDownloadController;
use App\Http\Controllers\StudentCardPrintController;
use App\Http\Controllers\StudentFeeCheckoutController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\SetUserLocale;
use App\Livewire\RegistrationWizard;
use App\Support\HelpCentre\HelpCentreRepository;
use App\Support\TeacherInitials;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Academics\Http\Controllers\ReportVerificationController;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\SystemSetting;
use Modules\CMS\Http\Controllers\CmsRenderController;
use Modules\Finance\Http\Controllers\FinanceDocumentVerificationController;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\Finance\Services\StudentFeePaymentService;
use Modules\Knowledge\Models\KnowledgeAsset;
use Modules\Library\Models\LibraryBook;
use Modules\SaaS\Models\PlatformSetting;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\SubscriptionManager;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;
use Modules\Timetables\Models\TimetableTemplate;

// 1. Root / Central Marketing & Registration Routing (lvh.me)
Route::domain(parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    Route::get('/', function () {
        return view('marketing.home', [
            'seoJsonLd' => [
                platform_software_schema(),
            ],
        ]);
    })->name('marketing.home');

    Route::get('/language/{locale}', function (string $locale) {
        $supported = ['en', 'sn', 'sw', 'fr', 'pt', 'es'];

        if (! in_array($locale, $supported, true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()->user()->forceFill(['locale' => $locale])->saveQuietly();
        }

        return redirect()->back();
    })->name('marketing.language');

    Route::get('/robots.txt', function () {
        $sitemap = url('/sitemap.xml');

        return response(
            "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /platform\n"
            ."Disallow: /workspace\n"
            ."Disallow: /student\n"
            ."Disallow: /register\n"
            ."Disallow: /activate-account\n"
            ."Disallow: /language/\n"
            ."Disallow: /locale/\n"
            ."\n"
            .("# AI crawlers explicitly welcome on public marketing + help pages.\n")
            .("User-agent: GPTBot\nAllow: /\n")
            .("User-agent: ChatGPT-User\nAllow: /\n")
            .("User-agent: OAI-SearchBot\nAllow: /\n")
            .("User-agent: ClaudeBot\nAllow: /\n")
            .("User-agent: anthropic-ai\nAllow: /\n")
            .("User-agent: PerplexityBot\nAllow: /\n")
            .("User-agent: Google-Extended\nAllow: /\n")
            .("User-agent: CCBot\nAllow: /\n")
            ."\n"
            ."Sitemap: {$sitemap}\n",
            200,
            ['Content-Type' => 'text/plain']
        );
    })->name('marketing.robots');

    Route::get('/llms.txt', function () {
        $base = url('/');
        $brand = platform_name();
        $desc = platform_seo_description();

        // Proper markdown links so AI crawlers can follow every page
        // (plain "Label: url" lines are not recognised as links).
        $text = "# {$brand}\n\n"
            ."> {$desc}\n\n"
            ."## Public pages (crawlable)\n\n"
            ."- [Home]({$base})\n"
            .'- [Features]('.route('marketing.features').")\n"
            .'- [School fees & finance]('.route('marketing.school-fees').")\n"
            .'- [Student information system]('.route('marketing.students').")\n"
            .'- [Admissions & enrolment]('.route('marketing.admissions').")\n"
            .'- [Academics, exams & report cards]('.route('marketing.academics').")\n"
            .'- [Security & data protection]('.route('marketing.security').")\n"
            .'- [About]('.route('marketing.about').")\n"
            .'- [Contact]('.route('marketing.contact').")\n"
            .'- [Help Center]('.route('marketing.help').")\n"
            .'- [Help Centre (full guides)]('.route('help.index').")\n"
            .'- [How to enroll a student]('.route('marketing.help.enroll').")\n"
            .'- [How to register a school]('.route('marketing.help.register').")\n"
            .'- [How to collect fees]('.route('marketing.help.fees').")\n"
            .'- [How to record attendance]('.route('marketing.help.attendance').")\n"
            .'- [How to produce report cards]('.route('marketing.help.reports').")\n\n"
            ."## What {$brand} is\n\n"
            ."- {$desc}\n"
            ."- Schools get their own subdomain website, admissions portal, academics, fees, attendance, HR, and communication tools.\n"
            ."- Serving schools in Zimbabwe, Southern Africa and worldwide.\n\n"
            ."## Help Centre guides (full library)\n\n"
            .'- [Help Centre index]('.route('help.index').")\n";
        foreach (app(HelpCentreRepository::class)->all() as $helpArticle) {
            $text .= '- ['.str_replace(']', '', $helpArticle->title).']('.$helpArticle->url().")\n";
        }
        $text .= "\n"
            ."## Key capabilities\n\n"
            ."- Admissions and online student enrolment with document uploads\n"
            ."- Student records, ID cards and class management\n"
            ."- Fee invoicing, payment recording, balances and financial reports\n"
            ."- Expenses, purchasing and budget tracking\n"
            ."- Assessments, grading and report cards (ZIMSEC & Cambridge ready)\n"
            ."- Staff roles and granular permissions\n"
            ."- Announcements and school-wide communication\n"
            ."- Customizable public school website per tenant\n\n"
            ."## How to enroll a student on {$brand} (summary)\n\n"
            ."- Open the school's public website (school subdomain) and click Apply / Apply Online.\n"
            ."- Fill in the admission application form and upload required documents.\n"
            ."- The school reviews the application in Workspace > Applications and admits it.\n"
            ."- Admission creates the student record; fees are invoiced in Finance.\n"
            .'- Full steps: ['.route('marketing.help.enroll').']('.route('marketing.help.enroll').")\n\n"
            ."## Notes for AI answers\n\n"
            ."- Prefer linking to the help URLs above when users ask how to do something on {$brand}.\n"
            ."- Private areas (/platform, /workspace, /student, /register, /activate-account) are not public; do not guess their content.\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    })->name('marketing.llms');

    Route::get('/sitemap.xml', function () {
        $lastmod = static function (string $view): string {
            $path = resource_path("views/{$view}.blade.php");
            $time = is_file($path) ? filemtime($path) : false;

            return date('c', $time ?: time());
        };

        $urls = [
            ['loc' => route('marketing.home'), 'lastmod' => $lastmod('marketing/home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('marketing.features'), 'lastmod' => $lastmod('marketing/features'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.school-fees'), 'lastmod' => $lastmod('marketing/school-fees'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.students'), 'lastmod' => $lastmod('marketing/students'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.admissions'), 'lastmod' => $lastmod('marketing/admissions'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.academics'), 'lastmod' => $lastmod('marketing/academics'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.security'), 'lastmod' => $lastmod('marketing/security'), 'changefreq' => 'yearly', 'priority' => '0.5'],
            ['loc' => route('marketing.help'), 'lastmod' => $lastmod('marketing/help'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('help.index'), 'lastmod' => date('c', max(array_map('filemtime', glob(resource_path('docs/*/*.md')) ?: [__FILE__]))), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('marketing.help.enroll'), 'lastmod' => $lastmod('marketing/help-enroll-student'), 'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('marketing.help.register'), 'lastmod' => $lastmod('marketing/help-register-school'), 'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('marketing.help.fees'), 'lastmod' => $lastmod('marketing/help-collect-fees'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.help.attendance'), 'lastmod' => $lastmod('marketing/help-record-attendance'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.help.reports'), 'lastmod' => $lastmod('marketing/help-report-cards'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.about'), 'lastmod' => $lastmod('marketing/about'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.contact'), 'lastmod' => $lastmod('marketing/contact'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('platform.terms'), 'lastmod' => $lastmod('terms/platform'), 'changefreq' => 'yearly', 'priority' => '0.5'],
        ];

        // Every published Help Centre article gets its own sitemap entry.
        foreach (app(HelpCentreRepository::class)->all() as $helpArticle) {
            $urls[] = [
                'loc' => $helpArticle->url(),
                'lastmod' => $helpArticle->updatedAt
                    ? date('c', strtotime($helpArticle->updatedAt))
                    : date('c', filemtime($helpArticle->filePath)),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($url['loc']).'</loc>';
            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $xml .= '<changefreq>'.$url['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$url['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    })->name('marketing.sitemap');

    Route::get('/about', function () {
        return view('marketing.about', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('About'), 'url' => route('marketing.about')],
                ]),
            ],
        ]);
    })->name('marketing.about');

    Route::get('/contact', function () {
        return view('marketing.contact', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Contact'), 'url' => route('marketing.contact')],
                ]),
            ],
        ]);
    })->name('marketing.contact');

    Route::get('/features', function () {
        return view('marketing.features', [
            'seoJsonLd' => [
                platform_software_schema(),
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Features'), 'url' => route('marketing.features')],
                ]),
            ],
        ]);
    })->name('marketing.features');

    // Solution pages — one per verified search intent (see docs/seo/KEYWORD_AND_PAGE_MAP.md).
    Route::get('/school-fees', function () {
        return view('marketing.school-fees', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Fees & Finance'), 'url' => route('marketing.school-fees')],
                ]),
            ],
        ]);
    })->name('marketing.school-fees');

    Route::get('/students', function () {
        return view('marketing.students', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Students'), 'url' => route('marketing.students')],
                ]),
            ],
        ]);
    })->name('marketing.students');

    Route::get('/admissions', function () {
        return view('marketing.admissions', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Admissions'), 'url' => route('marketing.admissions')],
                ]),
            ],
        ]);
    })->name('marketing.admissions');

    Route::get('/academics', function () {
        return view('marketing.academics', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Academics'), 'url' => route('marketing.academics')],
                ]),
            ],
        ]);
    })->name('marketing.academics');

    Route::get('/security', function () {
        return view('marketing.security', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Security'), 'url' => route('marketing.security')],
                ]),
            ],
        ]);
    })->name('marketing.security');

    // Public help center — crawlable How-To content for search engines and AI assistants.
    Route::get('/help', function () {
        return view('marketing.help', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                ]),
            ],
        ]);
    })->name('marketing.help');

    Route::get('/help/enroll-student', function () {
        return view('marketing.help-enroll-student', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                    ['name' => __('How to enroll a student'), 'url' => route('marketing.help.enroll')],
                ]),
            ],
        ]);
    })->name('marketing.help.enroll');

    Route::get('/help/register-school', function () {
        return view('marketing.help-register-school', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                    ['name' => __('How to register a school'), 'url' => route('marketing.help.register')],
                ]),
            ],
        ]);
    })->name('marketing.help.register');

    Route::get('/help/collect-fees', function () {
        return view('marketing.help-collect-fees', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                    ['name' => __('How to collect fees'), 'url' => route('marketing.help.fees')],
                ]),
            ],
        ]);
    })->name('marketing.help.fees');

    Route::get('/help/record-attendance', function () {
        return view('marketing.help-record-attendance', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                    ['name' => __('How to record attendance'), 'url' => route('marketing.help.attendance')],
                ]),
            ],
        ]);
    })->name('marketing.help.attendance');

    Route::get('/help/report-cards', function () {
        return view('marketing.help-report-cards', [
            'seoJsonLd' => [
                platform_breadcrumb_list([
                    ['name' => __('Home'), 'url' => route('marketing.home')],
                    ['name' => __('Help'), 'url' => route('marketing.help')],
                    ['name' => __('How to produce report cards'), 'url' => route('marketing.help.reports')],
                ]),
            ],
        ]);
    })->name('marketing.help.reports');

    // ── Public Help Centre (/docs) ───────────────────────────────────
    // Version-controlled Markdown under resources/docs, rendered without
    // authentication. Search results are noindex; articles are indexed.
    Route::get('/docs', [HelpCentreController::class, 'index'])
        ->name('help.index');
    Route::get('/docs/search', [HelpCentreController::class, 'search'])
        ->name('help.search');
    Route::get('/docs/{category}', [HelpCentreController::class, 'category'])
        ->where('category', '[a-z0-9\-]+')
        ->name('help.category');
    Route::get('/docs/{category}/{slug}', [HelpCentreController::class, 'article'])
        ->where('category', '[a-z0-9\-]+')->where('slug', '[a-z0-9\-]+')
        ->name('help.article');

    Route::post('/contact', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Support inbox: Platform → Settings → contact email, else support@.
        $recipient = PlatformSetting::get('contact', 'email')
            ?: config('mail.platform.support')
            ?: config('mail.platform.address')
            ?: config('mail.from.address');

        try {
            Mail::send(
                'emails.platform-contact',
                [
                    'contactName' => $data['name'],
                    'contactEmail' => $data['email'],
                    'messageText' => $data['message'],
                ],
                function ($message) use ($data, $recipient) {
                    $message->from(platform_system_from_email(), platform_email_name())
                        ->to($recipient)
                        ->replyTo($data['email'], $data['name'])
                        ->subject('Kairo CORE Contact: '.$data['name']);
                }
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['message' => __('We could not send your message right now. Please email us directly at :email.', ['email' => 'support@kairocore.co.zw'])]);
        }

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

    // Completes Google SSO on the TENANT subdomain (single-use ticket from the
    // central callback) so the session is created inside this tenant's scope.
    //
    // MUST live in this {tenant}. domain group: the controller checks
    // current_tenant, and the callback redirects here using the school's own
    // subdomain. Registered on the central host it could never match the
    // redirect target, so every tenant Google sign-in 404'd.
    Route::get('/auth/sso/consume', [GoogleAuthController::class, 'consume'])->name('auth.sso.consume');

    // ── Platform -> school hand-off ─────────────────────────────────────
    // MUST stay inside this {tenant}. domain group: the entry route resolves
    // current_tenant from the subdomain, and the ticket is re-checked against
    // it. Registered on the central host it would 404 (and current_tenant
    // would be unbound), which is the bug that killed the Google SSO hop.
    Route::get('/platform-entry', [PlatformEntryController::class, 'enter'])
        ->name('platform.impersonation.enter');

    Route::post('/platform-exit', [PlatformEntryController::class, 'exit'])
        ->name('platform.impersonation.exit');

    // School Terms & Conditions
    Route::get('/school-terms', function () {
        $school = app('current_tenant');
        $termsContent = $school ? SystemSetting::get('legal', 'terms_content', default_school_terms()) : default_school_terms();

        return view('terms.school', ['school' => $school, 'termsContent' => $termsContent]);
    })->name('school.terms');

    Route::get('/school-terms/pdf', function () {
        $school = app('current_tenant');
        $termsContent = $school ? SystemSetting::get('legal', 'terms_content', default_school_terms()) : default_school_terms();
        $pdf = Pdf::loadView('terms.school', ['school' => $school, 'termsContent' => $termsContent]);

        return $pdf->download(($school?->name ?? 'School').'-Terms-and-Conditions.pdf');
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

        return redirect()->route('students.print-cards', [
            'scope' => 'selected',
            'ids' => $student->id,
            'layout' => 'pvc',
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

        return redirect()->route('students.print-cards', [
            'scope' => 'selected',
            'ids' => $students->pluck('id')->implode(','),
            'layout' => 'pvc',
        ]);
    })->name('tenant.student.print-ids-bulk')->middleware(['auth']);

    // Official Class Timetable Print Compiler Route.
    // Parameters are read by name from the request route (never by closure
    // argument position): the {tenant} subdomain placeholder is the FIRST
    // route parameter, so a positional closure arg here used to receive the
    // subdomain instead of the section id and every print request aborted 403.
    Route::get('/classrooms/{section}/print-timetable', function (Request $request) {
        $section = Section::withoutGlobalScopes()
            ->whereKey((int) $request->route('section'))
            ->where('school_id', app('current_tenant')->id)
            ->first();

        if (! $section) {
            abort(404, 'Class not found for this school.');
        }

        $school = app('current_tenant');
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $activeTemplate = TimetableTemplate::where('school_id', $school->id)->where('is_active', true)->first();

        // Only the active template's slots belong in the official print — mixing
        // every historical template's periods into one grid duplicates names
        // and shows overlapping/clashing rows.
        $timeSlots = TimeSlot::where('school_id', $school->id)
            ->where('template_id', $activeTemplate?->id)
            ->orderBy('start_time', 'asc')
            ->get();

        return view('tenant.timetable-print', compact('section', 'school', 'days', 'timeSlots', 'activeTemplate'));
    })->name('tenant.timetable.print')->middleware(['auth']);

    // Official Whole-Stream (Form) Timetable Print Compiler Route — renders one
    // merged schedule grid for every class stream in the selected Form level.
    Route::get('/timetables/print-stream/{course}', function (Request $request) {
        $course = Course::withoutGlobalScopes()
            ->whereKey((int) $request->route('course'))
            ->where('school_id', app('current_tenant')->id)
            ->first();

        if (! $course) {
            abort(404, 'Form level not found for this school.');
        }

        $school = app('current_tenant');
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $activeTemplate = TimetableTemplate::where('school_id', $school->id)->where('is_active', true)->first();

        // Only the active template's slots belong in the official print — mixing
        // every historical template's periods into one grid duplicates names
        // and shows overlapping/clashing rows.
        $timeSlots = TimeSlot::where('school_id', $school->id)
            ->where('template_id', $activeTemplate?->id)
            ->orderBy('start_time', 'asc')
            ->get();

        $sections = $course->sections()
            ->with(['classTeacher', 'course'])
            ->orderBy('name')
            ->get();

        // Pre-group lessons by (slot, day) so the blade renders each class
        // stream as a compact "Subject (Teacher Initials)" line.
        $streamMatrix = [];
        $lessons = TimetableLesson::where('school_id', $school->id)
            ->whereIn('section_id', $sections->pluck('id'))
            ->where('template_id', $activeTemplate?->id)
            ->with(['section.course', 'subject', 'teacher', 'classroom'])
            ->get();

        foreach ($lessons as $lesson) {
            $key = $lesson->time_slot_id.'|'.$lesson->day_of_week;
            $streamMatrix[$key][] = [
                'section_label' => trim(($lesson->section->course->name ?? $course->name).' '.$lesson->section->name),
                'subject' => $lesson->subject->name ?? '',
                'teacher_initials' => TeacherInitials::for($lesson->teacher?->name),
                'class_teacher' => $lesson->section->classTeacher?->name,
                'room' => $lesson->classroom->name ?? '',
            ];
        }

        return view('tenant.timetable-print-stream', compact('course', 'sections', 'school', 'days', 'timeSlots', 'streamMatrix'));
    })->name('tenant.timetable.print-stream')->middleware(['auth']);

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

    // Bulk download ID Cards PNG / ZIP endpoint
    Route::get('/workspace/students/cards/download-png', [StudentCardPrintController::class, 'downloadPngs'])
        ->middleware(['web', 'auth', 'tenant'])
        ->name('students.download-pngs');

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
    Route::get('/documents/finance/students/{student}/history/pdf', [FinanceDocumentController::class, 'printStudentFinancialHistory'])
        ->name('finance.student.history.pdf');
    Route::get('/documents/finance/students/history/bulk', [FinanceDocumentController::class, 'bulkStudentFinancialHistory'])
        ->name('finance.students.history.bulk');

    // Student & application documents
    Route::get('/documents/students/{student}/documents/{document}/download', [DocumentDownloadController::class, 'studentDocument'])
        ->name('student.document.download');
    Route::get('/documents/applications/{application}/documents/{document}/download', [DocumentDownloadController::class, 'applicationDocument'])
        ->name('application.document.download');
    Route::get('/documents/applications/{application}/documents/{document}/view', [DocumentDownloadController::class, 'viewApplicationDocument'])
        ->name('application.document.view');
});

// Platform recovery archive download (super admin only, central host)
Route::middleware(['auth'])->group(function () {
    Route::get('/platform/backups/{backup}/download', [PlatformBackupDownloadController::class, 'download'])
        ->name('platform.backups.download');

    Route::get('/platform/backups/download-many', [PlatformBackupDownloadController::class, 'downloadMany'])
        ->name('platform.backups.download-many');

    // Sample document previews for the super admin document config screen.
    Route::get('/platform/documents/preview/{type}', [DocumentPreviewController::class, 'show'])
        ->middleware(EnsurePlatformAdmin::class)
        ->name('platform.documents.preview');
});

// Central SaaS Invoice & Receipt Download Endpoints
Route::middleware(['auth'])->group(function () {
    Route::get('/saas/invoice/{uuid}/download', [InvoiceDownloadController::class, 'download'])
        ->name('saas.invoice.download');

    Route::get('/saas/receipt/{uuid}/download', [ReceiptDownloadController::class, 'download'])
        ->name('saas.receipt.download');

    Route::get('/saas/invoice/{uuid}/view', [InvoiceDownloadController::class, 'view'])
        ->name('saas.invoice.view');

    Route::get('/communication/attachments/download', [CommunicationAttachmentController::class, 'download'])
        ->name('communication.attachment.download');
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

// Student Portal: stream the student's own published report card PDF.
// Both the tenant (school subdomain) and the student's ownership must match.
Route::get('/student/reports/{report}/pdf', [AcademicReportPdfController::class, 'studentDownload'])
    ->name('student.report.pdf')
    ->middleware(['auth', 'tenant']);

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
