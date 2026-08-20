<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationReceived;
use App\Mail\ContactFormMessage;
use App\Models\School;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Academics\Models\Course;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationDocument;
use Modules\CMS\Models\CmsFormSubmission;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;
use Modules\CMS\Services\CmsSeoService;
use Modules\CMS\Services\CmsTemplateService;
use Modules\CMS\Services\SiteTemplateCatalog;

class CmsRenderController extends Controller
{
    /**
     * Resolve and render tenant public website pages dynamically.
     */
    public function render(Request $request, $tenant = null, $slug = null, bool $preview = false)
    {
        $school = app('current_tenant');
        if (! $school) {
            $school = School::first();
        }

        if (! $school) {
            abort(404, 'Active school tenant context missing.');
        }

        $websiteLocale = SystemSetting::where('school_id', $school->id)->where('group', 'preferences')->where('key', 'website_language')->value('value');
        $websiteLocale = $websiteLocale ?: ($school->locale ?? 'en');

        $originalLocale = app()->getLocale();
        app()->setLocale($websiteLocale);

        try {

            $website = CmsWebsite::where('school_id', $school->id)->where('is_template_site', false)->first();
            if (! $website) {
                $website = CmsWebsite::create([
                    'school_id' => $school->id,
                    'is_template_site' => false,
                    'active_template' => 'heritage-editorial',
                    'font_primary' => 'Inter',
                    'font_secondary' => 'Outfit',
                    'navigation_menu' => [
                        ['label' => __('Home'), 'url' => '/'],
                        ['label' => __('About'), 'url' => '/about'],
                        ['label' => __('Admissions'), 'url' => '/apply-online'],
                        ['label' => __('News & Events'), 'url' => '/news-events'],
                        ['label' => __('Contact'), 'url' => '/contact'],
                        ['label' => __('Student Life'), 'url' => '/student-life'],
                    ],
                ]);
            }

            self::ensureAllStandardPages($website, $school->id);

            $page = null;
            if (is_null($slug) || $slug === '/' || $slug === '' || $slug === 'cms-render') {
                $page = CmsPage::where('cms_website_id', $website->id)->where('is_homepage', true)->first();
                if (! $page) {
                    $page = CmsPage::where('cms_website_id', $website->id)->first();
                }

                if (! $page) {
                    $page = CmsPage::create([
                        'school_id' => $school->id,
                        'cms_website_id' => $website->id,
                        'title' => __('Home'),
                        'slug' => 'home',
                        'is_homepage' => true,
                        'is_published' => true,
                        'blocks' => [
                            [
                                'id' => 'hero-1',
                                'type' => 'hero',
                                'title' => __('Nurturing Academic Excellence'),
                                'description' => __('A premier educational institution guiding next-generation achievements.'),
                                'cta_text' => 'Join Our Academy',
                                'cta_url' => '/apply-online',
                                'styles' => ['padding_top' => 'py-20', 'padding_bottom' => 'py-20'],
                            ],
                            [
                                'id' => 'about-1',
                                'type' => 'about_section',
                                'title' => __('Empowering Curiosity & Ethical Growth'),
                                'description' => __('Delivering accredited education tailored for leadership.'),
                                'styles' => ['padding_top' => 'py-16', 'padding_bottom' => 'py-16'],
                            ],
                            [
                                'id' => 'stats-1',
                                'type' => 'statistics',
                                'styles' => ['padding_top' => 'py-16', 'padding_bottom' => 'py-16'],
                            ],
                            [
                                'id' => 'cylinder-highlight',
                                'type' => 'cylinder_carousel',
                            ],
                        ],
                    ]);
                }
            } else {
                // 'admissions' and 'apply-online' are aliases for one single page.
                // Always resolve the single canonical record so the CMS Builder and
                // both public slugs interact with the exact same database row.
                if (in_array($slug, ['admissions', 'apply-online'], true)) {
                    $page = CmsPage::consolidateAdmissionsAlias($website->id);
                } else {
                    $page = CmsPage::where('cms_website_id', $website->id)->where('slug', $slug)->first();
                }

                if (in_array($slug, ['admissions', 'apply-online'], true) && ! $page) {
                    $layout = CmsTemplateService::pageLayoutsFor('apply-online', false)['admission_1'] ?? null;
                    $blocks = $layout ? array_map(
                        fn (string $type) => CmsTemplateService::starterBlock($type),
                        $layout['blocks']
                    ) : [];

                    $page = CmsPage::create([
                        'school_id' => $school->id,
                        'cms_website_id' => $website->id,
                        'title' => __('Admissions'),
                        'slug' => 'apply-online',
                        'is_homepage' => false,
                        'is_published' => true,
                        'hide_from_nav' => false,
                        'sort_order' => 4,
                        'page_template' => $website->active_template,
                        'blocks' => $blocks,
                        'draft_blocks' => $blocks,
                    ]);
                }
            }

            if (! $page || (! $page->is_published && ! ($preview && Auth::check()))) {
                if (Auth::check() && $page && $page->draft_blocks) {
                    // Allow preview
                } else {
                    abort(404, 'The requested website page is not published.');
                }
            }

            $stats = [
                'students_count' => DB::table('students')->where('school_id', $school->id)->count(),
                'courses_count' => DB::table('courses')->where('school_id', $school->id)->count(),
                'books_count' => DB::table('inventory_items')->where('school_id', $school->id)->count(),
                'teachers_count' => DB::table('users')->where('school_id', $school->id)->count(),
            ];

            $news = CmsTemplateService::resolveDynamicBlockData('news_feed', $school->id);
            $events = CmsTemplateService::resolveDynamicBlockData('events_calendar', $school->id);
            $staff = CmsTemplateService::resolveDynamicBlockData('staff_directory', $school->id);

            $templates = CmsTemplateService::getTemplates();
            $pageTemplate = CmsTemplateService::canonicalTemplate($page->page_template ?? $website->active_template);
            $activeTheme = $templates[$pageTemplate] ?? $templates['heritage-editorial'];
            $schemaMarkup = CmsSeoService::generateSchemaJson($school, $website, $page);
            $navigationPages = CmsPage::where('cms_website_id', $website->id)
                ->where('is_published', true)->where('hide_from_nav', false)->orderBy('sort_order')->get();

            $blocks = $preview && Auth::check() && ! empty($page->draft_blocks)
                ? $page->draft_blocks
                : ($page->blocks ?? []);

            return view('modules.cms.renderer', [
                'school' => $school,
                'website' => $website,
                'page' => $page,
                'theme' => $activeTheme,
                'blocks' => $blocks,
                'stats' => $stats,
                'news' => $news,
                'events' => $events,
                'staff' => $staff,
                'schemaMarkup' => $schemaMarkup,
                'navigationPages' => $navigationPages,
            ]);
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function preview(Request $request, string $slug)
    {
        return $this->render($request, $slug, true);
    }

    public function submitApplication(Request $request)
    {
        if ($this->honeypotTriggered($request)) {
            return redirect()->route('cms-render', ['slug' => 'apply-online']);
        }

        $school = app('current_tenant')
            ?? School::where('id', $request->input('school_id'))->first()
            ?? School::first();
        if (! $school) {
            return back()->with('error', 'School context not resolved.');
        }

        $course = Course::withoutTenantScope()
            ->where('school_id', $school->id)
            ->find($request->input('course_id'));
        $isEntryLevel = $course ? Application::isEntryLevel($course->name) : true;

        $transferRequired = ! $isEntryLevel && filter_var(
            SystemSetting::get('admission', 'transfer_letter_required', false),
            FILTER_VALIDATE_BOOL
        );

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_email' => ['required', 'email', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:30'],
            // A public form must never be able to attach an application to a
            // course belonging to another school.
            'course_id' => ['required', Rule::exists('courses', 'id')->where('school_id', $school->id)],
            'applying_year' => ['nullable', 'string', 'max:10'],
            'applying_term' => ['nullable', 'string', 'max:50'],
            // Supporting documents are optional at application time. They can
            // be requested by admissions during review instead of silently
            // preventing the applicant's record from being created.
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*.title' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'transfer_letter' => $transferRequired
                ? 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'
                : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            do {
                $applicationNumber = 'APP-'.date('Y').'-'.strtoupper(Str::random(6));
            } while (DB::table('applications')->where('application_number', $applicationNumber)->exists());

            $application = Application::create([
                'school_id' => $school->id,
                'application_number' => $applicationNumber,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'parent_name' => $validated['parent_name'],
                'parent_email' => $validated['parent_email'],
                'parent_phone' => $validated['parent_phone'],
                'course_id' => $validated['course_id'],
                'applying_level' => $course ? $course->name : null,
                'applying_year' => $validated['applying_year'] ?? (string) now()->year,
                'applying_term' => $validated['applying_term'] ?? null,
                'status' => 'pending',
            ]);

            $this->storeApplicationDocuments($request, $application, $school->id);

            DB::commit();
            // Persist first. Notifications and email are secondary delivery
            // channels, so an email configuration problem can never make a
            // successful application disappear.
            $this->notifyStaffOfNewApplication($application, $school);

            return redirect()->route('tenant.home')->with('application_ref', $applicationNumber);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Unable to complete application submission. Error: '.$e->getMessage());
        }
    }

    /**
     * Detect a filled honeypot. The honeypot's field name is generated randomly
     * per session so Chrome's autofill (which matches fixed names like
     * "company_website") and naive bots can no longer pre-fill it. Check the
     * session-scoped name, falling back to the legacy fixed name for older
     * sessions where the random name was never generated.
     */
    protected function honeypotTriggered(Request $request): bool
    {
        foreach (array_filter([session('cms_honeypot_field_name'), 'company_website']) as $name) {
            if (! empty($request->input($name))) {
                return true;
            }
        }

        return false;
    }

    protected function storeApplicationDocuments(Request $request, Application $application, int $schoolId): void
    {
        if ($request->hasFile('transfer_letter')) {
            $file = $request->file('transfer_letter');

            if ($file && $file->isValid()) {
                $path = $file->store('admission-docs/'.$application->id, 'public');

                ApplicationDocument::create([
                    'school_id' => $schoolId,
                    'application_id' => $application->id,
                    'document_type' => 'transfer_letter',
                    'title' => __('Verified Transfer Letter'),
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                $application->transfer_letter_path = $path;
                $application->transfer_letter_verified = false;
            }
        }

        foreach ((array) $request->input('documents', []) as $index => $docData) {
            $title = trim((string) ($docData['title'] ?? ''));
            $file = $request->file("documents.{$index}.file");

            if ($title === '' || ! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('admission-docs/'.$application->id, 'public');

            ApplicationDocument::create([
                'school_id' => $schoolId,
                'application_id' => $application->id,
                'document_type' => 'other',
                'title' => $title,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        if (isset($application->transfer_letter_path) && $application->transfer_letter_path) {
            $application->save();
        }
    }

    protected function notifyStaffOfNewApplication(Application $application, School $school): void
    {
        $schoolId = $school->id ?? $application->school_id;

        try {
            $staff = User::withoutTenantScope()
                ->where('school_id', $schoolId)
                ->get();

            if ($staff->isNotEmpty()) {
                Notification::send($staff, new NewApplicationNotification($application));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if (! filter_var(SystemSetting::get('admission', 'notify_email_enabled', true), FILTER_VALIDATE_BOOL)) {
                return;
            }

            $admissionEmail = SystemSetting::get('admission', 'contact_email', '');
            if (! filter_var($admissionEmail, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            app(TenantEmailConfigurationService::class)->queueSend(
                new ApplicationReceived(
                    $application,
                    $admissionEmail,
                    $school->name ?? 'Our School',
                ),
                EmailCategory::Admissions,
                $school,
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function submitContact(Request $request)
    {
        if ($this->honeypotTriggered($request)) {
            return back()->with('contact_success', 'Thank you — your message has been sent to the school.');
        }

        $school = app('current_tenant') ?? School::first();
        abort_unless($school, 404, 'School context not resolved.');

        $validated = $request->validate([
            'page_slug' => 'required|string|max:100',
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => 'required|email|max:255',
            'phone' => ['required', 'string', 'max:30'],
            'message' => __('required|string|min:2|max:5000'),
        ]);

        $website = CmsWebsite::where('school_id', $school->id)->where('is_template_site', false)->first();
        $page = CmsPage::where('cms_website_id', $website->id ?? 0)
            ->where('slug', $validated['page_slug'])
            ->where('is_published', true)
            ->firstOrFail();

        $contactBlock = collect($page->blocks ?? [])->first(fn (array $block) => ($block['type'] ?? null) === 'contact_map');
        $recipient = data_get($contactBlock, 'email');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->withErrors(['contact' => 'This school has not configured a contact email address yet.']);
        }

        $submission = CmsFormSubmission::create([
            'school_id' => $school->id,
            'cms_page_id' => $page->id,
            'form_handle' => 'contact',
            'form_data' => [
                'first_name' => $validated['first_name'], 'last_name' => $validated['last_name'],
                'email' => $validated['email'], 'phone' => $validated['phone'], 'message' => $validated['message'],
            ],
            'meta' => ['ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'recipient' => $recipient],
            'status' => 'new',
        ]);

        $sent = app(TenantEmailConfigurationService::class)->queueSend(
            new ContactFormMessage($recipient, $validated, $school->name),
            EmailCategory::Communication,
            $school,
        );

        if (! $sent) {
            $submission->update(['notes' => 'Delivery failed: tenant email configuration missing or invalid.']);

            return back()->withInput()->withErrors(['contact' => 'We could not send your message right now. Please try again shortly.']);
        }

        return back()->with('contact_success', 'Thank you — your message has been sent to the school.');
    }

    public function sitemap()
    {
        $school = app('current_tenant') ?? School::first();
        if (! $school) {
            abort(404);
        }

        $xml = CmsSeoService::generateSitemapXml($school->id);

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $school = app('current_tenant') ?? School::first();
        if (! $school) {
            abort(404);
        }

        return response(CmsSeoService::generateRobotsTxt(), 200, ['Content-Type' => 'text/plain']);
    }

    public static function ensureAllStandardPages(CmsWebsite $website, int $schoolId): void
    {
        // The site seeds from the catalog for the canonical template; legacy
        // stored keys are normalised so an existing school keeps working.
        $activeTemplate = CmsTemplateService::canonicalTemplate($website->active_template);
        $pages = SiteTemplateCatalog::pages($activeTemplate);

        foreach ($pages as $slug => $sp) {
            $exists = CmsPage::where('cms_website_id', $website->id)
                ->where(fn ($q) => $q->where('slug', $slug)->orWhere('slug', $slug === 'apply-online' ? 'admissions' : ''))
                ->exists();

            if (! $exists) {
                CmsPage::create([
                    'school_id' => $schoolId,
                    'cms_website_id' => $website->id,
                    'title' => $sp['title'],
                    'slug' => $slug,
                    'is_homepage' => $slug === 'home',
                    'is_published' => true,
                    'page_template' => $activeTemplate,
                    'blocks' => $sp['blocks'],
                ]);
            }
        }
    }
}
