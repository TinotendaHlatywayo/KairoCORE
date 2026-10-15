<?php

use App\Models\School;
use App\Services\TerminologyService;
use Carbon\CarbonInterface;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Mode;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Models\SystemSetting;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Inventory\Models\InventoryItem;
use Modules\SaaS\Models\PlatformSetting;

if (! function_exists('term')) {
    function term(string $key, string $default): string
    {
        return App::make(TerminologyService::class)->get($key, $default);
    }
}

if (! function_exists('honeypot_field_name')) {
    /**
     * A session-scoped, random honeypot field name. Chrome autofills fields
     * whose name/id match its heuristics (e.g. "company_website"), which made
     * legit submissions get silently discarded as spam. A random name generated
     * per session defeats both autofill and naive bots that target fixed names.
     */
    function honeypot_field_name(): string
    {
        $key = 'cms_honeypot_field_name';
        if (! session()->has($key)) {
            session([$key => 'f_'.Str::random(10)]);
        }

        return (string) session($key);
    }
}

if (! function_exists('platform_email_address')) {
    /**
     * The platform-level sending account. Tenant email configurations must
     * never reuse this identity as their sender.
     */
    function platform_email_address(): string
    {
        return (string) config('mail.platform.address', config('mail.from.address', 'hello@example.com'));
    }
}

if (! function_exists('platform_email_name')) {
    function platform_email_name(): string
    {
        return (string) config('mail.platform.name', config('mail.from.name', 'Kairo CORE'));
    }
}

if (! function_exists('platform_notification_email')) {
    /**
     * Inbox that receives new-school-registration alerts. Editable under
     * Platform → Settings → Notifications. Defaults to hlatwayne@gmail.com.
     */
    function platform_notification_email(): string
    {
        try {
            $configured = PlatformSetting::get('notifications', 'super_admin_email');
        } catch (Throwable $e) {
            $configured = null;
        }

        if (is_string($configured) && filter_var(trim($configured), FILTER_VALIDATE_EMAIL)) {
            return trim($configured);
        }

        return 'hlatwayne@gmail.com';
    }
}

if (! function_exists('platform_tenant_message_email')) {
    /**
     * Inbox that receives an alert whenever a tenant messages the platform.
     * Editable under Platform → Settings → Notifications.
     * Defaults to hlatwayne@gmail.com.
     */
    function platform_tenant_message_email(): string
    {
        try {
            $configured = PlatformSetting::get('notifications', 'tenant_message_email');
        } catch (Throwable $e) {
            $configured = null;
        }

        if (is_string($configured) && filter_var(trim($configured), FILTER_VALIDATE_EMAIL)) {
            return trim($configured);
        }

        return 'hlatwayne@gmail.com';
    }
}

if (! function_exists('platform_system_from_email')) {
    /**
     * The address platform-generated mail is sent "from". Editable under
     * Platform → Settings → Notifications so the operator can switch the
     * responsible sender without a redeploy. Falls back to the SMTP account
     * (MAIL_FROM_ADDRESS) when unset.
     */
    function platform_system_from_email(): string
    {
        try {
            $configured = PlatformSetting::get('notifications', 'system_from_email');
        } catch (Throwable $e) {
            $configured = null;
        }

        if (is_string($configured) && filter_var(trim($configured), FILTER_VALIDATE_EMAIL)) {
            return trim($configured);
        }

        return platform_email_address();
    }
}

if (! function_exists('platform_name')) {
    /**
     * The public SaaS platform name. Configurable under
     * Platform → Settings → SaaS Branding → "SaaS Platform Name".
     */
    function platform_name(): string
    {
        try {
            $name = PlatformSetting::get('branding', 'platform_name');
        } catch (Throwable $e) {
            $name = null;
        }

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : (string) config('app.name', 'Kairo CORE');
    }
}

if (! function_exists('platform_logo_url')) {
    /**
     * The platform logo uploaded under Platform → Settings → SaaS Branding.
     * Falls back to the bundled transparent logo.
     */
    function platform_logo_url(): string
    {
        return platform_branding_asset_url('platform_logo', 'images/logo-transparent.png');
    }
}

if (! function_exists('platform_favicon_url')) {
    /**
     * The platform favicon uploaded under Platform → Settings → SaaS Branding.
     * Falls back to the bundled favicon.
     */
    function platform_favicon_url(): string
    {
        return platform_branding_asset_url('platform_favicon', 'favicon.ico');
    }
}

if (! function_exists('platform_branding_asset_url')) {
    /**
     * Resolve an uploaded branding asset (logo/favicon) to a public URL.
     * FileUpload values are stored as JSON arrays of storage paths.
     */
    function platform_branding_asset_url(string $key, string $fallback): string
    {
        try {
            $value = PlatformSetting::get('branding', $key);
        } catch (Throwable $e) {
            $value = null;
        }

        $path = null;
        if (is_array($value)) {
            $path = $value[0] ?? null;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            $path = is_array($decoded) ? ($decoded[0] ?? null) : $value;
        }

        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return asset($fallback);
    }
}

if (! function_exists('platform_seo_description')) {
    /**
     * Default meta description for the public platform pages.
     */
    function platform_seo_description(): string
    {
        return (string) __(platform_name().' is a multi-tenant school management platform with admissions, academics, fees, attendance, communication, and a customizable public website for every school.');
    }
}

if (! function_exists('supported_locales')) {
    /**
     * Single source of truth for the site languages (code => label). Used by
     * resolve_app_locale(), the marketing language switcher and the live stat
     * counts, so adding a locale here updates all three at once.
     *
     * @return array<string, string>
     */
    function supported_locales(): array
    {
        return [
            'en' => 'English',
            'sn' => 'Shona',
            'sw' => 'Swahili',
            'fr' => 'Français',
            'pt' => 'Português',
            'es' => 'Español',
        ];
    }
}

if (! function_exists('marketing_live_stats')) {
    /**
     * Live counts from this install, backing the marketing stats marquee, the
     * About page stats and the hero stat cards. Uses the query builder (never
     * Eloquent) so the BelongsToTenant global scopes cannot bite on public
     * marketing pages. Cached for the request; falls back to static defaults
     * if the database is unavailable (deploy time, migrations, CLI).
     *
     * @return array{schools: string, students: string, staff: string, languages: string, modules: string}
     */
    function marketing_live_stats(): array
    {
        static $stats = null;

        if ($stats !== null) {
            return $stats;
        }

        try {
            $studentRoleIds = DB::table('custom_roles')->where('name', 'Student')->pluck('id')->all();

            $staff = DB::table('users')
                ->whereNull('deleted_at')
                ->whereNotNull('school_id')
                ->where(function ($query) {
                    $query->whereNull('requested_role')->orWhere('requested_role', '<>', 'student');
                });

            if ($studentRoleIds !== []) {
                $staff->where(function ($query) use ($studentRoleIds) {
                    $query->whereNull('custom_role_id')->orWhereNotIn('custom_role_id', $studentRoleIds);
                });
            }

            $stats = [
                'schools' => (string) DB::table('schools')->whereNull('deleted_at')->count(),
                'students' => (string) DB::table('students')->whereNull('deleted_at')->count(),
                'staff' => (string) $staff->count(),
                'users' => (string) DB::table('users')->whereNull('deleted_at')->count(),
                'languages' => (string) count(supported_locales()),
                'modules' => (string) count(config('modules', [])),
            ];
        } catch (Throwable $e) {
            $stats = [
                'schools' => '250',
                'students' => '45000',
                'staff' => '3200',
                'users' => '48200',
                'languages' => '6',
                'modules' => '19',
            ];
        }

        return $stats;
    }
}

if (! function_exists('marketing_content_defaults')) {
    /**
     * Shipped copy for the public marketing site. These values are the single
     * source of truth for the "Website Content" editor in the platform panel:
     * each field toggles between the stored PlatformSetting and the default.
     *
     * Every string goes through __() so the shipped defaults are translated in
     * all six site languages. Admin overrides can be customised per language
     * via "{key}@{locale}" PlatformSettings — see marketing_content().
     *
     * @return array<string, string>
     */
    function marketing_content_defaults(): array
    {
        return [
            // ── Global ──────────────────────────────────────────────
            'announcement' => '',
            'nav_logo' => '',
            'footer_logo' => '',
            'footer_tagline' => __('Cloud-based school management platform built for schools.'),
            'meta_title' => '',
            'meta_description' => '',

            // ── Home · hero ─────────────────────────────────────────
            'hero_badge' => __('Built for schools globally with multi-language support'),
            'hero_title' => __('Run your whole school from one platform'),
            'hero_subtitle' => __('KairoCORE brings admissions, academics, fees, attendance, HR, communication and more together in one secure, cloud-based system, so your team spends less time on paperwork and more time on students.'),
            'hero_image' => 'https://images.pexels.com/photos/15984014/pexels-photo-15984014.jpeg?auto=compress&cs=tinysrgb&w=1600',
            'hero_primary_label' => __('Book a personal demo'),
            'hero_primary_url' => '',
            'hero_secondary_label' => __('Explore features'),
            'hero_secondary_url' => '',
            'hero_trust_1' => __('Easy to set up'),
            'hero_trust_2' => __('Secure & reliable'),
            'hero_trust_3' => __('Multi-currency billing'),
            'hero_stat_1_label' => __('Modules included'),
            'hero_stat_1_value' => marketing_live_stats()['modules'],
            'hero_stat_2_label' => __('Schools running KairoCORE'),
            'hero_stat_2_value' => marketing_live_stats()['schools'],

            // ── Home · sections ─────────────────────────────────────
            'modules_title' => __('Everything your school runs on, in one place'),
            'modules_subtitle' => __('From the first admission enquiry to the final report card, every department works from the same system.'),
            'modules_cta_label' => __('See every feature'),
            'benefits_title' => __('Why schools choose KairoCORE'),
            'benefits_subtitle' => __('A single, dependable system that grows with your school.'),
            'steps_title' => __('Up and running in days, not months'),
            'steps_subtitle' => __('Getting your school onto KairoCORE is straightforward.'),
            'cta_title' => __('Ready to see KairoCORE in action?'),
            'cta_subtitle' => __('Book a personal demo tailored to your school, no obligation.'),
            'cta_primary_label' => __('Book a personal demo'),
            'cta_primary_url' => '',
            'cta_secondary_label' => __('Explore features'),
            'cta_secondary_url' => '',

            // ── Features page ───────────────────────────────────────
            'features_badge' => __('Platform'),
            'features_title' => __('Every tool your school needs'),
            'features_subtitle' => __('From admissions to analytics, KairoCORE brings the day-to-day running of your school into one connected platform.'),
            'features_cta_title' => __('Not sure where to start?'),
            'features_cta_subtitle' => __('Book a personal demo and we will show you the modules that matter most to your school.'),
            'features_cta_primary_label' => __('Book a personal demo'),
            'features_cta_secondary_label' => __('Contact us'),

            // ── About page ──────────────────────────────────────────
            'about_badge' => __('About us'),
            'about_title' => __('School software built for the way schools actually run'),
            'about_intro' => __('KairoCORE is a modern, all-in-one school management system. It brings admissions, academics, finance, HR, communication, health, inventory and daily operations into one secure, cloud-based platform, for schools of every size.'),
            'about_image' => 'https://images.pexels.com/photos/15984015/pexels-photo-15984015.jpeg?auto=compress&cs=tinysrgb&w=1440',
            'about_founded_label' => __('Founded in'),
            'about_founded_value' => 'Harare, Zimbabwe',
            'about_why_badge' => __('Why KairoCORE'),
            'about_why_title' => __('Why schools choose KairoCORE'),
            'about_story_badge' => __('Our story'),
            'about_story_title' => __('Built in Zimbabwe, for schools everywhere'),
            'about_story_p1' => __('KairoCORE started with a simple observation: school administrators were drowning in paperwork, duplicate records and disconnected spreadsheets. We built one system that mirrors how schools really work, term by term, class by class, fee by fee, and made it affordable and simple enough for any school to adopt.'),
            'about_story_p2' => __('Today, primary and secondary schools run admissions, exams, fees and parent communication on KairoCORE every day.'),
            'about_cta_title' => __('Want to see how KairoCORE fits your school?'),
            'about_cta_subtitle' => __('Book a personal demo and we will walk you through it, module by module.'),

            // ── Contact page ────────────────────────────────────────
            'contact_badge' => __('Contact'),
            'contact_title' => __('Reach out to us'),
            'contact_intro' => __('Have questions about KairoCORE or want to schedule a personalized demo? Reach out to us through any of the channels below.'),
            'contact_form_title' => __('Send us a message'),
            'contact_form_name_label' => __('Your name'),
            'contact_form_email_label' => __('Your email'),
            'contact_form_message_label' => __('Message'),
            'contact_form_placeholder_name' => __('Tendai Marufu'),
            'contact_form_placeholder_message' => __('Tell us a little about your school and what you need...'),
            'contact_form_submit_label' => __('Send message'),
            'contact_whatsapp_title' => __('WhatsApp us'),
            'contact_whatsapp_note' => __('Fastest replies during office hours'),
            'contact_email_title' => __('Email support'),
            'contact_email_note' => __('For quotes, demos and support'),
            'contact_office_title' => __('Office hours'),
            'contact_office_hours' => __('Monday – Friday, 08:00 – 17:00 (CAT)'),
            'contact_office_location' => __('Harare, Zimbabwe'),
            'contact_follow_label' => __('Follow'),
            'contact_image' => 'https://images.pexels.com/photos/3184339/pexels-photo-3184339.jpeg?auto=compress&cs=tinysrgb&w=1440',
            'contact_card_title' => __('Talk to a school specialist'),
            'contact_card_text' => __('We understand school terms, fee cycles and exam seasons, and we speak your language.'),
        ];
    }
}

if (! function_exists('marketing_list_defaults')) {
    /**
     * Shipped defaults for the repeater-backed lists on the marketing site.
     * Structure fields (icons, keys, categories) are not translated; all
     * human-readable text goes through __().
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    function marketing_list_defaults(): array
    {
        $px = fn (int $id, int $w = 1440) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";

        return [
            'home_stats' => [
                ['value' => marketing_live_stats()['schools'], 'suffix' => '', 'label' => __('Schools onboard'), 'icon' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332c0-.458-.186-.895-.515-1.22a1.65 1.65 0 0 0-1.22-.515H8.265c-.458 0-.895.186-1.22.515-.33.325-.515.762-.515 1.22V21'],
                ['value' => marketing_live_stats()['students'], 'suffix' => '', 'label' => __('Students managed'), 'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                ['value' => marketing_live_stats()['users'], 'suffix' => '', 'label' => __('Total users'), 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z'],
                ['value' => '99', 'suffix' => '%', 'label' => __('Uptime'), 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['value' => marketing_live_stats()['languages'], 'suffix' => '', 'label' => __('Languages'), 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
            ],
            'home_modules' => [
                ['name' => __('Admissions'), 'desc' => __('Applications, enrolment and complete student records.'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                ['name' => __('Academics'), 'desc' => __('Classes, subjects, timetables and report cards.'), 'icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
                ['name' => __('Finance & Fees'), 'desc' => __('Fee structures, invoicing and multi-currency billing.'), 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33'],
                ['name' => __('Attendance'), 'desc' => __('Daily student and staff attendance with alerts.'), 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['name' => __('HR & Payroll'), 'desc' => __('Staff records, leave management and payroll.'), 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                ['name' => __('Communication'), 'desc' => __('Announcements and email or WhatsApp alerts to parents.'), 'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
                ['name' => __('Exams & Results'), 'desc' => __('Exam scheduling, grading schemes and result publishing.'), 'icon' => 'M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6'],
                ['name' => __('Library'), 'desc' => __('Book catalogue, issuance tracking, returns and fines.'), 'icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
                ['name' => __('Health & Wellness'), 'desc' => __('Student health records, clinic visits and medical alerts.'), 'icon' => 'M21 7.5V18a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18V7.5m18 0A2.25 2.25 0 0 0 18.75 5.25H5.25A2.25 2.25 0 0 0 3 7.5m18 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L4.07 9.659A2.25 2.25 0 0 1 3 7.743V7.5'],
            ],
            'home_benefits' => [
                ['name' => __('One system for the whole school'), 'desc' => __('Admissions, academics, finance, HR and communication share the same data, so nothing has to be captured twice.'), 'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25A2.25 2.25 0 0 1 8.25 10.5H6A2.25 2.25 0 0 1 3.75 8.25V6ZM13.5 6A2.25 2.25 0 0 1 15.75 3.75h2.25A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
                ['name' => __('Save hours of admin every week'), 'desc' => __('Automated fees, invoices, report cards and alerts replace repetitive paperwork so your staff can focus on learners.'), 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['name' => __('Reach parents where they are'), 'desc' => __('Send announcements and timely updates by email or WhatsApp, and let parents follow attendance, fees and results.'), 'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 16.5h3'],
                ['name' => __('Secure, cloud-based and current'), 'desc' => __('Your data is backed up and always up to date, with role-based access so everyone sees only what they should.'), 'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
            ],
            'home_steps' => [
                ['title' => __('Register your school'), 'desc' => __('Create your school account in minutes and get your own secure workspace.')],
                ['title' => __('Set up your school'), 'desc' => __('Add classes, staff, fee structures and branding, with our team on hand to help.')],
                ['title' => __('Go live'), 'desc' => __('Start managing admissions, academics, fees and communication from day one.')],
            ],
            'features_list' => [
                ['key' => 'admissions', 'cat' => 'admin', 'name' => __('Admissions'), 'desc' => __('Manage student enrolment, applications and admission workflow.'), 'detail' => __('Run your whole admissions season from one place. Families apply online, your team reviews applications, places learners and issues offer letters, while every document, sibling link and placement stays attached to a single student record that follows the child through every grade.'), 'sub1' => __('Online applications'), 'sub2' => __('Student registration'), 'sub3' => __('Admission approval'), 'image' => $px(35305047)],
                ['key' => 'academics', 'cat' => 'academic', 'name' => __('Academics'), 'desc' => __('Classes, timetables, subjects, grading and report cards.'), 'detail' => __('Set up subjects, class streams and once-off timetables, then grade against your own scheme: marks, letters or percentages. Term reports are generated per class in seconds, ready to print or share with parents.'), 'sub1' => __('Class management'), 'sub2' => __('Timetable scheduling'), 'sub3' => __('Report cards'), 'image' => $px(5905554)],
                ['key' => 'students', 'cat' => 'academic', 'name' => __('Students'), 'desc' => __('Comprehensive student profiles and records.'), 'detail' => __('Every learner gets a complete profile: guardian contacts, medical notes, transport, fee history, attendance and results, searchable in one directory your whole team can trust.'), 'sub1' => __('Student profiles'), 'sub2' => __('Attendance tracking'), 'sub3' => __('Student directory'), 'image' => $px(14909652)],
                ['key' => 'finance', 'cat' => 'admin', 'name' => __('Finance & Fees'), 'desc' => __('Fees, billing, invoicing and financial management.'), 'detail' => __('Build fee structures per grade and term, generate invoices in bulk and reconcile payments in US dollars, ZiG or any currency you bill in. Bursars see balances, arrears and daily receipts on one dashboard.'), 'sub1' => __('Fee structure'), 'sub2' => __('Invoicing'), 'sub3' => __('Financial history'), 'image' => $px(8872369)],
                ['key' => 'hr', 'cat' => 'admin', 'name' => __('HR & Payroll'), 'desc' => __('Staff management, leave, attendance and payroll.'), 'detail' => __('Keep contracts, qualifications and emergency contacts in one staff register. Track leave approvals and run payroll with allowances and deductions calculated automatically each month.'), 'sub1' => __('Staff records'), 'sub2' => __('Leave management'), 'sub3' => __('Payroll processing'), 'image' => $px(8550496)],
                ['key' => 'attendance', 'cat' => 'campus', 'name' => __('Attendance'), 'desc' => __('Track student and staff attendance with ease.'), 'detail' => __('Form teachers mark the register from any phone or desktop in under a minute. Absences roll up into class and grade reports, and guardians can be alerted the same day.'), 'sub1' => __('Daily attendance'), 'sub2' => __('Attendance reports'), 'sub3' => __('Absence alerts'), 'image' => $px(19982406)],
                ['key' => 'communications', 'cat' => 'campus', 'name' => __('Communications'), 'desc' => __('Announcements and timely alerts to parents and staff.'), 'detail' => __('Publish circulars, event reminders and urgent notices once, and deliver them by email or WhatsApp to the right guardians, classes or the whole school, with delivery status you can check.'), 'sub1' => __('School announcements'), 'sub2' => __('Email or WhatsApp alerts'), 'sub3' => __('Parent engagement'), 'image' => $px(36752223)],
                ['key' => 'library', 'cat' => 'academic', 'name' => __('Library'), 'desc' => __('Manage library resources and circulation.'), 'detail' => __('Catalogue every title, issue and return books against a reader card, and let overdue fines accrue automatically so the library runs itself between visits.'), 'sub1' => __('Book catalogue'), 'sub2' => __('Issuance tracking'), 'sub3' => __('Returns & fines'), 'image' => $px(3966555)],
                ['key' => 'health', 'cat' => 'campus', 'name' => __('Health & Wellness'), 'desc' => __('Student health records, clinic visits, medications and alerts.'), 'detail' => __('Record allergies, chronic conditions and clinic visits on the student file, log medication administered, and flag critical medical alerts that staff must see before an activity or trip.'), 'sub1' => __('Health records'), 'sub2' => __('Clinic visits'), 'sub3' => __('Medical alerts'), 'image' => $px(5430213)],
                ['key' => 'inventory', 'cat' => 'admin', 'name' => __('Inventory & Procurement'), 'desc' => __('Stock management, procurement, requisitions and assets.'), 'detail' => __('Track consumables and equipment across stores, raise requisitions that route for approval, and follow every purchase order from request to delivery.'), 'sub1' => __('Stock management'), 'sub2' => __('Requisitions & purchase orders'), 'sub3' => __('Asset tracking'), 'image' => $px(8069554)],
                ['key' => 'exams', 'cat' => 'academic', 'name' => __('Exams & Results'), 'desc' => __('Examinations, grading schemes and result publication.'), 'detail' => __('Schedule exams by form and subject, capture marks with your own grading scheme, and publish ranked results, transcripts and analyse-of-results the moment marking is complete.'), 'sub1' => __('Exam scheduling'), 'sub2' => __('Grade setup'), 'sub3' => __('Result publication'), 'image' => $px(32719717)],
                ['key' => 'lms', 'cat' => 'academic', 'name' => __('LMS'), 'desc' => __('Learning management with assignments and resources.'), 'detail' => __('Share lesson materials, set assignments and collect submissions online. Teachers mark and give feedback in the same place, and students see every deadline in one feed.'), 'sub1' => __('Assignments'), 'sub2' => __('Course materials'), 'sub3' => __('Student submissions'), 'image' => $px(11025019)],
                ['key' => 'boarding', 'cat' => 'campus', 'name' => __('Boarding & Hostel'), 'desc' => __('Hostel management, rooms and allocations.'), 'detail' => __('Allocate beds by house and dormitory, record night-by-night boarding attendance, and bill boarding fees alongside term charges so housemasters always know who is on campus.'), 'sub1' => __('Room allocation'), 'sub2' => __('Hostel attendance'), 'sub3' => __('Boarding fees'), 'image' => $px(33613739)],
                ['key' => 'reports', 'cat' => 'admin', 'name' => __('Reports & Analytics'), 'desc' => __('Comprehensive reports and data insights.'), 'detail' => __('Turn daily operations into decisions: enrolment trends, fee collection rates, subject performance and staff summaries, exportable to PDF or spreadsheet for your governing body.'), 'sub1' => __('Financial reports'), 'sub2' => __('Academic reports'), 'sub3' => __('Custom reports'), 'image' => $px(7173020)],
                ['key' => 'cms', 'cat' => 'admin', 'name' => __('Website CMS'), 'desc' => __('Manage school website content easily.'), 'detail' => __('Publish news, calendars, policies and photo galleries to your public school website from the same system your admin team already uses, no developer needed.'), 'sub1' => __('Page management'), 'sub2' => __('Media library'), 'sub3' => __('Content editor'), 'image' => $px(6956760)],
            ],
            'about_pillars' => [
                ['title' => __('Our mission'), 'desc' => __('To give every school the tools to run smoothly, reducing administrative burden, improving transparency, and freeing teachers and administrators to focus on learners.'), 'icon' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.63 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.109-8.59a20.005 20.005 0 0 0-5.499-2.58m7.175 5.499a19.92 19.92 0 0 0 2.498-6.256M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z'],
                ['title' => __('Our vision'), 'desc' => __('A future where every school runs on reliable, affordable, modern technology, with real-time insight into academics, finances and day-to-day operations.'), 'icon' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z'],
                ['title' => __('How we build'), 'desc' => __('With schools, not just for them. Every module is shaped by real school workflows, tested with administrators, and kept simple enough to use every day.'), 'icon' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z'],
            ],
            'about_why_points' => [
                ['text' => __('One connected platform for every department')],
                ['text' => __('Built for African schools with multi-currency fees and WhatsApp alerts')],
                ['text' => __('Simple enough for busy admin teams to adopt quickly')],
                ['text' => __('Role-based access and regular backups keep your data safe')],
                ['text' => __('Reports and dashboards that turn daily data into decisions')],
                ['text' => __('A team that understands school operations, on hand to help')],
            ],
            'about_stats' => [
                ['value' => marketing_live_stats()['schools'], 'suffix' => '', 'label' => __('Schools onboard')],
                ['value' => marketing_live_stats()['students'], 'suffix' => '', 'label' => __('Students managed')],
                ['value' => marketing_live_stats()['languages'], 'suffix' => '', 'label' => __('Languages supported')],
                ['value' => '99', 'suffix' => '%', 'label' => __('Platform uptime')],
            ],
        ];
    }
}

if (! function_exists('marketing_settings_map')) {
    /**
     * All PlatformSetting rows in the "marketing" group, keyed by setting key.
     * List values are stored as JSON arrays; scalars as plain strings. Read
     * once per request.
     *
     * @return array<string, mixed>
     */
    function marketing_settings_map(): array
    {
        static $map = null;

        if ($map === null) {
            $map = [];

            try {
                foreach (PlatformSetting::where('group', 'marketing')->get() as $setting) {
                    $decoded = json_decode((string) $setting->value, true);
                    $map[$setting->key] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                        ? $decoded
                        : (string) $setting->value;
                }
            } catch (Throwable $e) {
                $map = [];
            }
        }

        return $map;
    }
}

if (! function_exists('marketing_content')) {
    /**
     * Read an editable marketing-site scalar, falling back to the shipped copy.
     *
     * Resolution order:
     *   1. "{key}@{locale}" — a per-language override saved from the editor
     *   2. "{key}"          — the base (default-language) override
     *   3. the shipped default, which is __()-translated at runtime
     */
    function marketing_content(string $key, ?string $default = null): string
    {
        $map = marketing_settings_map();
        $locale = app()->getLocale();

        $value = $map[$key.'@'.$locale] ?? null;
        if (is_array($value)) {
            $value = null;
        }

        if ($value === null || $value === '') {
            $value = $map[$key] ?? null;
            if (is_array($value)) {
                $value = null;
            }
        }

        if ($value === null || $value === '') {
            $value = marketing_content_defaults()[$key] ?? $default;
        }

        return (string) ($value ?? '');
    }
}

if (! function_exists('marketing_raw_setting')) {
    /**
     * Raw stored override for a marketing key in a given locale (or the base
     * key when $locale is null). Returns null when nothing has been stored —
     * used by the settings editor to prefill fields.
     */
    function marketing_raw_setting(string $key, ?string $locale = null): mixed
    {
        $map = marketing_settings_map();

        return $map[$locale ? $key.'@'.$locale : $key] ?? null;
    }
}

if (! function_exists('marketing_merged_list')) {
    /**
     * A marketing list (repeater) for the current locale, merged over the
     * shipped defaults. Stored items override defaults positionally; stored
     * extras beyond the defaults are appended. Empty storage falls back to
     * the full default list so the site never renders blank sections.
     *
     * @return array<int, array<string, mixed>>
     */
    function marketing_merged_list(string $key): array
    {
        $defaults = marketing_list_defaults()[$key] ?? [];
        $map = marketing_settings_map();
        $locale = app()->getLocale();

        $stored = $map[$key.'@'.$locale] ?? null;
        if (! is_array($stored) || $stored === []) {
            $stored = $map[$key] ?? null;
        }
        if (! is_array($stored) || $stored === []) {
            return $defaults;
        }

        // The stored list is authoritative for length (deletions stick);
        // positionally inherit structural fields (icon, key, image) from the
        // defaults, and treat blank strings as "reset to default".
        $merged = [];
        foreach (array_values($stored) as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $clean = array_filter($item, fn ($v) => $v !== '' && $v !== null);
            $merged[] = array_merge($defaults[$i] ?? [], $clean);
        }

        return $merged ?: $defaults;
    }
}

if (! function_exists('marketing_resolve_image')) {
    /**
     * Turn a stored image value (absolute URL, "images/..." asset path or a
     * public-disk storage path from an upload) into a full URL. Falls back to
     * $fallback when empty.
     */
    function marketing_resolve_image(?string $value, string $fallback = ''): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            $value = trim($fallback);
        }
        if ($value === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $value)) {
            return $value;
        }
        if (str_starts_with($value, 'images/')) {
            return asset($value);
        }

        return Storage::disk('public')->url(ltrim($value, '/'));
    }
}

if (! function_exists('marketing_image')) {
    /**
     * Resolved URL for an editable marketing image key (with per-locale
     * override support via marketing_content()).
     */
    function marketing_image(string $key, string $fallback = ''): string
    {
        return marketing_resolve_image(marketing_content($key), $fallback);
    }
}

if (! function_exists('platform_og_image_url')) {
    /**
     * Social share (Open Graph / Twitter) image for the platform pages.
     * Prefers an uploaded OG image, then the bundled 1200px social card.
     */
    function platform_og_image_url(): string
    {
        return platform_branding_asset_url('platform_og_image', 'images/og-kairocore.jpg');
    }
}

if (! function_exists('platform_website_schema')) {
    /**
     * schema.org WebSite node for the platform marketing site.
     */
    function platform_website_schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => platform_name(),
            'url' => url('/'),
        ];
    }
}

if (! function_exists('platform_organization_schema')) {
    /**
     * schema.org Organization node for the platform marketing site.
     */
    function platform_organization_schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => platform_name(),
            'url' => url('/'),
            'logo' => platform_logo_url(),
        ];
    }
}

if (! function_exists('platform_breadcrumb_list')) {
    /**
     * Build a schema.org BreadcrumbList node.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     */
    function platform_breadcrumb_list(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $index => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'] ?? '',
                'item' => $item['url'] ?? '',
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}

if (! function_exists('school_favicon_url')) {
    /**
     * The active tenant's favicon: school-uploaded first, then the platform
     * favicon. Safe to call outside a tenant context.
     */
    function school_favicon_url(): string
    {
        try {
            if (app()->bound('current_tenant')) {
                $favicon = SystemSetting::get('branding', 'favicon_path');

                if (is_array($favicon)) {
                    $favicon = $favicon[0] ?? null;
                }

                if (is_string($favicon) && $favicon !== '' && Storage::disk('public')->exists($favicon)) {
                    return asset('storage/'.$favicon);
                }
            }
        } catch (Throwable $e) {
            // fall through to platform favicon
        }

        return platform_favicon_url();
    }
}

if (! function_exists('is_platform_email')) {
    function is_platform_email(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return strtolower(trim($email)) === strtolower(trim(platform_email_address()));
    }
}

if (! function_exists('school_website_url')) {
    /**
     * Resolve the school's public website URL for printed documents.
     *
     * Prefers the custom website configured under System Settings →
     * Institution Profile, then any website_url set directly on the school
     * record, then the automatically assigned subdomain URL
     * (e.g. http://rujeko.lvh.me:8000/).
     */
    function school_website_url($school, ?string $configured = null): ?string
    {
        if (! empty($configured)) {
            return $configured;
        }

        if (! empty($school->website_url)) {
            return $school->website_url;
        }

        $subdomain = $school->subdomain ?? null;
        if (empty($subdomain)) {
            return null;
        }

        $parsed = parse_url((string) config('app.url'));
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'lvh.me';
        if (! empty($parsed['port'])) {
            $host .= ':'.$parsed['port'];
        }

        // ResolveTenant rewrites config('app.url') to the current request root
        // (e.g. https://tinwayacademy.lvh.me), so the host may already carry the
        // tenant subdomain. Detect that and return the URL as-is instead of
        // prepending the subdomain a second time.
        if ($subdomain && str_starts_with(strtolower($host), strtolower($subdomain).'.')) {
            return $scheme.'://'.$host.'/';
        }

        return $scheme.'://'.$subdomain.'.'.$host.'/';
    }
}

if (! function_exists('document_school_profile')) {
    /**
     * Resolve the school identity/contact details shown on printed finance
     * documents (invoice, receipt, statement).
     *
     * The Institution Profile tab in System Settings (system_settings group
     * "profile") is the source of truth. Falls back to the direct `schools`
     * table columns, then to sensible placeholders.
     */
    function document_school_profile($school, array $config = []): array
    {
        return [
            'name' => ($config['school_name'] ?? null) ?: ($school->name ?? 'School'),
            'motto' => $school->motto ?? 'Education for Excellence',
            'address' => ($config['address'] ?? null) ?: ($school->physical_address ?? 'Not Configured'),
            'phone' => ($config['phone'] ?? null) ?: ($school->phone_number ?? 'N/A'),
            'email' => ($config['email'] ?? null) ?: ($school->email_address ?? 'N/A'),
            'website' => school_website_url($school, $config['website_url'] ?? null) ?? 'N/A',
        ];
    }
}

if (! function_exists('finance_document_theme')) {
    /**
     * Resolve the active billing-document template for a school and return the
     * concrete CSS values used by the invoice/receipt/statement PDF blades.
     *
     * The "minimal_compact" preset renders strictly in black, white and grey,
     * so every semantic accent (success/danger/tints) collapses to grayscale.
     */
    function finance_document_theme($template, ?string $type, $school): array
    {
        if (! $template && $type && $school) {
            $template = FinanceDocumentTemplate::resolveFor((int) $school->id, $type);
        }

        $theme = $template ? $template->design_theme : 'classic_line';
        $layout = $template ? $template->resolveConfig() : FinanceDocumentTemplate::$themeDefaults['classic_line'];
        $sections = $template ? $template->resolveSections() : FinanceDocumentTemplate::sectionsFor('classic_line', []);

        $mono = (bool) (FinanceDocumentTemplate::$themeDefaults[$theme]['mono'] ?? ($theme === 'minimal_compact'));

        return [
            'theme' => $theme,
            'structure' => $layout['structure'] ?? 'classic',
            'header_color' => $layout['header_color'] ?? '#1e3a8a',
            'accent_color' => $layout['accent_color'] ?? '#1e3a8a',
            'table_header_bg' => $layout['table_header_bg'] ?? '#1e3a8a',
            'font_family' => $layout['font_family'] ?? 'Helvetica, sans-serif',
            'success_color' => $mono ? '#111827' : '#15803d',
            'danger_color' => $mono ? '#111827' : '#991b1b',
            'light_blue' => $mono ? '#111827' : '#1e40af',
            'light_green' => $mono ? '#111827' : '#166534',
            'light_red' => $mono ? '#111827' : '#991b1b',
            'blue_tint' => $mono ? '#f9fafb' : '#eff6ff',
            'green_tint' => $mono ? '#f9fafb' : '#f0fdf4',
            'red_tint' => $mono ? '#f9fafb' : '#fef2f2',
            'soft_border' => $mono ? '#e5e7eb' : '#bbf7d0',
            'sections' => $sections,
        ];
    }
}

if (! function_exists('finance_document_logo_path')) {
    /**
     * Resolve the logo shown on a finance document header.
     *
     * The template logo (header.logo) wins; when absent it falls back to the
     * school branding logo (billing config logo_path). The value may be a stored
     * relative path, an absolute filesystem path, or a web URL (used by the
     * live preview for just-uploaded temporary files).
     */
    function finance_document_logo_path(array $header, array $config): ?string
    {
        $logo = $header['logo'] ?? '';

        if (is_array($logo)) {
            $logo = array_values($logo)[0] ?? '';
        }

        $logo = is_string($logo) ? $logo : '';

        if ($logo !== '') {
            if (str_starts_with($logo, 'http')) {
                return $logo;
            }

            if (file_exists(public_path('storage/'.$logo))) {
                return public_path('storage/'.$logo);
            }
        }

        if (($config['show_logo'] ?? false) && ! empty($config['logo_path']) && file_exists(public_path('storage/'.$config['logo_path']))) {
            return public_path('storage/'.$config['logo_path']);
        }

        return null;
    }
}

if (! function_exists('resolve_public_asset_path')) {
    /**
     * Resolve a stored file path to a public-relative path (or null).
     * Handles both legacy "public/" paths and modern Laravel public-disk
     * paths (files stored under storage/app/public).
     */
    function resolve_public_asset_path(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $trimmed = ltrim($path, '/');

        // If it's already a full URL or starts with storage/, return as-is
        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, 'storage/')) {
            return $trimmed;
        }

        // Check using Storage disk (most reliable for symlinks)
        if (Storage::disk('public')->exists($trimmed)) {
            return 'storage/'.$trimmed;
        }

        // Fallback: check common filesystem paths
        if (file_exists(public_path('storage/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        if (file_exists(storage_path('app/public/'.$trimmed))) {
            return 'storage/'.$trimmed;
        }

        if (file_exists(public_path($trimmed))) {
            return $trimmed;
        }

        // Last resort: assume it's a valid public disk path and construct URL
        // This handles cases where file_exists fails due to symlink/permission issues
        // but the file actually exists. The browser will show 404 if truly missing.
        return 'storage/'.$trimmed;
    }
}

if (! function_exists('random_library_cover')) {
    /**
     * Return a random default library cover image URL.
     * Randomly picks from the 4 generated book cover design images.
     */
    function random_library_cover(): string
    {
        $covers = [
            'images/Book_cover_design_for_Kairo_202609010032.jpeg',
            'images/Book_cover_design_for_Kairo_202609010040.jpeg',
            'images/Book_cover_design_prompt_202609010035.jpeg',
            'images/Designing_default_school_book_cover_202609010037.jpeg',
        ];

        return asset($covers[array_rand($covers)]);
    }
}

if (! function_exists('student_photo_src')) {
    /**
     * Absolute filesystem path of a student's photo for PDF embedding.
     * Falls back to the gender-appropriate default placeholder image
     * (no_profile_female.jpg for girls, no_profile_male.png for boys).
     */
    function student_photo_src($student): string
    {
        $resolved = resolve_public_asset_path($student->photo_path ?? null);
        if ($resolved) {
            return public_path($resolved);
        }

        $fallback = ($student->gender === 'female')
            ? 'images/no_profile_female.jpg'
            : 'images/no_profile_male.png';

        return public_path($fallback);
    }
}

if (! function_exists('id_card_hex_to_rgba')) {
    /**
     * Convert any CSS color (hex / rgb / rgba / named) to an rgba() string
     * carrying the given alpha (0..1). Used to apply logo/card background
     * transparency to ID card elements in a DomPDF-safe way.
     */
    function id_card_hex_to_rgba(string $color, float $alpha = 1.0): string
    {
        $c = trim($color);
        $a = max(0, min(1, (float) $alpha));

        if (preg_match('/^#([a-fA-F0-9]{6})$/', $c, $m)) {
            $r = hexdec(substr($m[1], 0, 2));
            $g = hexdec(substr($m[1], 2, 2));
            $b = hexdec(substr($m[1], 4, 2));

            return "rgba({$r}, {$g}, {$b}, {$a})";
        }

        if (preg_match('/^#([a-fA-F0-9]{3})$/', $c, $m)) {
            $r = hexdec(str_repeat($m[1][0], 2));
            $g = hexdec(str_repeat($m[1][1], 2));
            $b = hexdec(str_repeat($m[1][2], 2));

            return "rgba({$r}, {$g}, {$b}, {$a})";
        }

        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $c, $m)) {
            return "rgba({$m[1]}, {$m[2]}, {$m[3]}, {$a})";
        }

        return $c;
    }
}

if (! function_exists('id_card_short_address')) {
    /**
     * Truncate a card address on a whole-word boundary so DomPDF never chops
     * mid-word into an ugly fragment like "Harare" -> "Hara..". Long addresses
     * are cut after the last whole word that still fits inside $max characters
     * and any truncation is marked with a single clean ellipsis.
     */
    function id_card_short_address(?string $address, int $max = 44): string
    {
        $text = trim((string) ($address ?: 'Borrowdale, Harare'));
        $width = max(16, (int) $max);

        if (mb_strlen($text) <= $width) {
            return $text;
        }

        $cut = mb_substr($text, 0, $width);

        if (preg_match('/\s/m', (string) $cut)) {
            $cut = mb_substr($cut, 0, mb_strrpos($cut, ' '));
        }

        $cut = rtrim($cut, " \t\n\r\0\x0B,.");

        return $cut.'…';
    }
}

if (! function_exists('id_card_gradient_data_uri')) {
    /**
     * Rasterise a 135° linear gradient between two colors into a base64 PNG
     * data URI. DomPDF cannot render CSS linear-gradient backgrounds, so the
     * gradient is pre-rendered with GD (when available) and cached on disk so
     * the browser preview, the printed PDF and the PNG export are identical.
     */
    function id_card_gradient_data_uri(string $start, string $end, int $width, int $height, float $opacity = 1.0): ?string
    {
        if (! function_exists('imagecreatetruecolor') || $width < 2 || $height < 2) {
            return null;
        }

        $width = max(2, (int) $width);
        $height = max(2, (int) $height);
        $opacity = max(0, min(1, (float) $opacity));
        $key = sha1(implode('|', [$start, $end, $width, $height, $opacity]));

        $dir = storage_path('app/public/id-card-bg-cache');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir."/{$key}.png";
        if (is_file($file)) {
            $data = (string) @file_get_contents($file);

            return $data !== '' ? 'data:image/png;base64,'.base64_encode($data) : null;
        }

        $img = @imagecreatetruecolor($width, $height);
        if ($img === false) {
            return null;
        }

        // Parse both stops into [r, g, b].
        $hex = function (string $color): array {
            $c = trim($color);
            if (preg_match('/^#([a-fA-F0-9]{6})$/', $c, $m)) {
                return [hexdec(substr($m[1], 0, 2)), hexdec(substr($m[1], 2, 2)), hexdec(substr($m[1], 4, 2))];
            }
            if (preg_match('/^#([a-fA-F0-9]{3})$/', $c, $m)) {
                return [hexdec(str_repeat($m[1][0], 2)), hexdec(str_repeat($m[1][1], 2)), hexdec(str_repeat($m[1][2], 2))];
            }
            if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $c, $m)) {
                return [(int) $m[1], (int) $m[2], (int) $m[3]];
            }

            return [255, 255, 255];
        };

        [$r1, $g1, $b1] = $hex($start);
        [$r2, $g2, $b2] = $hex($end);

        $lerp = function (float $t, int $a, int $b): int {
            return (int) round($a + ($b - $a) * $t);
        };

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // 135deg diagonal: normalized position across both axes.
                $t = (($x / max(1, $width - 1)) + ($y / max(1, $height - 1))) / 2;
                $t = max(0, min(1, $t));
                $col = imagecolorallocate($img, $lerp($t, $r1, $r2), $lerp($t, $g1, $g2), $lerp($t, $b1, $b2));
                imagesetpixel($img, $x, $y, $col);
            }
        }

        if ($opacity < 1.0) {
            $overlay = imagecolorallocatealpha($img, 255, 255, 255, (int) round((1 - $opacity) * 127));
            imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $overlay);
        }

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        @file_put_contents($file, $png);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}

if (! function_exists('id_card_file_data_uri')) {
    /**
     * Base64 data URI of a stored asset (public disk / public path / URL),
     * used to embed logos, photos and watermark images in DomPDF.
     */
    function id_card_file_data_uri(mixed $path): ?string
    {
        if (is_string($path)) {
            $trimmedPath = trim($path);
            if (str_starts_with($trimmedPath, '[') || str_starts_with($trimmedPath, '{')) {
                $decoded = json_decode($trimmedPath, true);
                if (is_array($decoded)) {
                    $path = reset($decoded) ?: null;
                }
            }
        }
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }
        if (empty($path) || ! is_string($path)) {
            return null;
        }

        $trimmed = ltrim($path, '/');

        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        $file = null;
        $cleanPath = str_replace('storage/', '', $trimmed);

        $candidates = [
            storage_path('app/public/'.$cleanPath),
            public_path('storage/'.$cleanPath),
            public_path($trimmed),
            storage_path('app/public/'.$trimmed),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }

        if (! $file || ! is_file($file)) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($file) : 'image/png';

        return 'data:'.($mime ?: 'image/png').';base64,'.base64_encode((string) @file_get_contents($file));
    }

    /**
     * Generate a QR code as a base64 data URI using the local chillerlan/php-qrcode library.
     *
     * The PNG is rendered with ECC level H (30% redundancy) and a standard 4-module quiet
     * zone. The pixel scale is derived from the QR's actual module count so the output is
     * generated as close as possible to the requested $size in pixels, producing a crisp,
     * high-resolution image that scans reliably at any display size.
     *
     * Falls back to the external API only if the library is unavailable.
     */
    function id_card_generate_qr(string $data, int $size = 600): string
    {
        try {
            if (class_exists(QRCode::class)) {
                $probe = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'eccLevel' => EccLevel::H,
                    'scale' => 1,
                    'addQuietzone' => true,
                    'quietzoneSize' => 4,
                ]);
                $probeCode = new QRCode($probe);
                foreach (Mode::INTERFACES as $interface) {
                    if ($interface::validateString($data)) {
                        $probeCode->addSegment(new $interface($data));
                        break;
                    }
                }
                $matrix = $probeCode->getQRMatrix();

                $moduleCount = max(1, $matrix->moduleCount);
                $scale = max(2, (int) ceil(max(1, (int) $size) / $moduleCount));

                $options = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'eccLevel' => EccLevel::H,
                    'scale' => $scale,
                    'addQuietzone' => true,
                    'quietzoneSize' => 4,
                    'outputBase64' => false,
                    'imageTransparent' => false,
                    'drawLightModules' => true,
                    'backgroundColor' => 'FFFFFF',
                    'gdImageUseUpscale' => false,
                ]);
                $pngData = (new QRCode($options))->render($data);

                return 'data:image/png;base64,'.base64_encode((string) $pngData);
            }
        } catch (Throwable $e) {
            // Fall through to external API
        }

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&ecc=H&format=png&data='.urlencode($data);
        $raw = @file_get_contents($url);

        return $raw ? 'data:image/png;base64,'.base64_encode($raw) : $url;
    }
}

if (! function_exists('current_tenant')) {
    /**
     * Resolve the current school from the tenant container binding, the session,
     * or the authenticated user, in that order.
     */
    function current_tenant(): ?School
    {
        if (app()->has('current_tenant') && app('current_tenant') instanceof School) {
            return app('current_tenant');
        }

        if (session()->has('current_tenant') && session('current_tenant') instanceof School) {
            return session('current_tenant');
        }

        $user = auth()->user();
        if ($user && $user->school_id) {
            return Cache::remember("tenant.school:{$user->school_id}", 300, function () use ($user) {
                return School::find($user->school_id);
            });
        }

        return null;
    }
}

if (! function_exists('resolve_app_locale')) {
    /**
     * Resolve the active locale from the session, the authenticated user, or
     * the current school, in that order. Falls back to the app fallback locale.
     * Safe to call outside HTTP contexts (queue jobs, mail) once the tenant has
     * been bound via current_tenant().
     *
     * Panel-scoped: the platform admin uses session('locale_admin') so tenant
     * language changes never leak into the platform interface.
     */
    function resolve_app_locale(): string
    {
        $school = current_tenant();
        $user = auth()->user();

        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
        $currentHost = request()->getHost();
        $isCentralDomain = $currentHost === $baseHost;

        $isPlatform = str_starts_with(request()->path(), 'platform');
        $sessionKey = $isPlatform ? 'locale_admin' : 'locale';

        // Priority 1: Explicit session choice (allows language switching)
        $locale = session($sessionKey);

        // Priority 2: If not central domain, check user & school locale
        if (! $locale && ! $isCentralDomain) {
            $locale = $user?->locale ?: ($school?->locale ?? null);
        }

        // Priority 3: Fallback (English for central domain if unset, otherwise app fallback)
        if (! $locale) {
            $locale = $isCentralDomain ? 'en' : config('app.fallback_locale', 'en');
        }

        $supported = array_keys(supported_locales());

        return in_array($locale, $supported, true) ? $locale : 'en';
    }
}

if (! function_exists('tenant_feature')) {
    /**
     * Step 1: Feature flags scoped per tenant.
     * Evaluates whether a feature is enabled for the current tenant context.
     * Falls back to base config/default if no tenant override exists.
     */
    function tenant_feature(string $featureKey, bool $default = false): bool
    {
        try {
            $tenant = current_tenant();
            if (! $tenant) {
                return (bool) config("features.{$featureKey}", $default);
            }

            $override = SystemSetting::get('features', $featureKey, null);
            if ($override !== null) {
                return filter_var($override, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (Throwable $e) {
            // Fallback gracefully during unrun migrations or boot
        }

        return (bool) config("features.{$featureKey}", $default);
    }
}

if (! function_exists('tenant_config')) {
    /**
     * Step 2: Tenant configuration inheritance with override layers.
     * Merges base configuration with runtime tenant-specific overrides.
     */
    function tenant_config(string $key, mixed $default = null): mixed
    {
        $baseValue = config($key, $default);

        try {
            $tenant = current_tenant();
            if (! $tenant) {
                return $baseValue;
            }

            // Check if there is a tenant setting override for this key
            $parts = explode('.', $key);
            $group = $parts[0] ?? 'general';
            $settingKey = $parts[1] ?? $key;

            $override = SystemSetting::get($group, $settingKey, null);
            if ($override !== null) {
                return $override;
            }
        } catch (Throwable $e) {
            // Fallback gracefully
        }

        return $baseValue;
    }
}

if (! function_exists('default_school_terms')) {
    function default_school_terms(): string
    {
        return '<h3>School Terms of Service & Student/Staff Conduct Agreement</h3>'.
               '<p>Welcome to our school portal. By registering an account and using this educational platform, you agree to abide by the following school-specific terms and policies:</p>'.
               '<ol>'.
               '<li><strong>Conduct & Academic Integrity:</strong> All students, staff, and parents agree to uphold the highest standards of academic honesty, respectful communication, and ethical behavior.</li>'.
               '<li><strong>Data Privacy & Acceptable Use:</strong> Users must not share login credentials, access unauthorized student records, or misuse school communication channels.</li>'.
               '<li><strong>Compliance with School Regulations:</strong> All activities on this platform are governed by school administration policies and applicable educational regulations.</li>'.
               '</ol>';
    }
}

if (! function_exists('email_branding')) {
    /**
     * Resolve the branding identity used by outgoing emails.
     *
     * Schools control their own automatically-sent emails (activation,
     * registration, admissions...) via Settings → Email Branding; the platform
     * controls its own (registration receipts to admins, SaaS billing) via
     * Platform Settings → Email Branding. Resolution order for school emails:
     * school email-branding settings → school profile fields → platform
     * email-branding settings → Kairo CORE defaults.
     *
     * @return array{logo_url:?string,company_name:string,company_address:?string,company_phone:?string,company_email:?string}
     */
    function email_branding(?School $school = null): array
    {
        $platform = function (string $key, mixed $default = null): mixed {
            try {
                return PlatformSetting::get('email', $key, $default);
            } catch (Throwable) {
                return $default;
            }
        };

        // Platform-level values first (they double as fallbacks).
        $logo = $platform('logo_path');
        $name = $platform('company_name') ?: config('app.name');
        $address = $platform('company_address');
        $phone = $platform('company_phone');
        $email = $platform('company_email');

        $normalizeLogo = function ($value): ?string {
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            if (blank($value)) {
                return null;
            }

            $relative = str_starts_with((string) $value, 'http') || str_starts_with((string) $value, 'storage/')
                ? str_replace('storage/', '', (string) $value)
                : $value;

            return file_exists(public_path('storage/'.$relative))
                ? asset('storage/'.$relative)
                : null;
        };

        $logoUrl = $normalizeLogo($logo);

        if ($school !== null) {
            $readSchoolSetting = function (string $key) use ($school): mixed {
                try {
                    $row = SystemSetting::query()
                        ->where('school_id', $school->id)
                        ->where('group', 'email')
                        ->where('key', $key)
                        ->value('value');

                    $decoded = is_string($row) ? json_decode($row, true) : $row;

                    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) === false && $decoded !== null
                        ? $decoded
                        : $row;
                } catch (Throwable) {
                    return null;
                }
            };

            $logoUrl = $normalizeLogo($readSchoolSetting('logo_path'))
                ?? $normalizeLogo($school->logo_path)
                ?? $logoUrl;
            $name = $readSchoolSetting('company_name') ?: ($school->name ?: $name);
            $address = $readSchoolSetting('company_address') ?: ($school->physical_address ?: $address);
            $phone = $readSchoolSetting('company_phone') ?: ($school->phone_number ?: $phone);
            $email = $readSchoolSetting('company_email') ?: ($school->email_address ?: $email);
        }

        return [
            'logo_url' => $logoUrl ?? platform_logo_url(),
            'company_name' => $name ?: config('app.name'),
            'company_address' => filled($address) ? trim((string) $address) : null,
            'company_phone' => filled($phone) ? trim((string) $phone) : null,
            'company_email' => filled($email) ? strtolower(trim((string) $email)) : null,
        ];
    }
}

if (! function_exists('platform_document_config')) {
    /**
     * The dynamic configuration for every platform-printed financial document
     * (invoices, receipts and account statements). Values stored by the super
     * admin under Platform Settings → Documents win; anything not set falls
     * back to the KairoCORE_Invoice.docx design defaults (coral/navy/off-white).
     *
     * $fallback lets callers requesting one key get a single value instead of
     * the whole merged array.
     */
    function platform_document_config(?string $fallback = null): mixed
    {
        $branding = (function (): array {
            try {
                return email_branding();
            } catch (Throwable) {
                return [];
            }
        })();

        $defaults = [
            'primary_color' => '#EF5F4D',
            'dark_color' => '#1F2E43',
            'light_fill' => '#F3F5F8',
            'watermark_enabled' => true,
            'watermark_opacity' => 0.06,
            'logo' => null,
            'watermark_image' => null,
            'business_name' => (string) ($branding['company_name'] ?? config('app.name', 'Kairo CORE')),
            'from_line_1' => $branding['company_address'] ?? null,
            'from_line_2' => null,
            'from_phone' => $branding['company_phone'] ?? null,
            'from_email' => isset($branding['company_email']) ? strtolower((string) $branding['company_email']) : null,
            'payment_channels' => [
                ['method' => 'EcoCash', 'number' => '0785556855'],
                ['method' => 'Bank (CABS USD Account)', 'number' => '1149411511'],
            ],
            'callout_title' => 'Payment Details',
            'callout_body' => 'Please use the invoice number as your payment reference.',
            'cross_border_notice' => 'All international invoices can be settled via multi-currency or cross-border payment rails.',
            'closing_title' => 'Thank you for your business!',
            'closing_subtitle' => 'Please use the invoice number as your payment reference.',
            'closing_font_size' => '13',
            'closing_font_weight' => 'bold',
            'closing_align' => 'center',
            'date_format' => 'd F Y',
        ];

        try {
            $rows = PlatformSetting::query()->where('group', 'documents')->get();
            foreach ($rows as $row) {
                $value = json_decode((string) $row->value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $value = $row->value;
                }
                $defaults[$row->key] = $value;
            }
        } catch (Throwable) {
            // During migrations / first boot there is no settings table yet.
        }

        if ($fallback !== null) {
            return $defaults[$fallback] ?? null;
        }

        return $defaults;
    }
}

if (! function_exists('document_watermark_color')) {
    /**
     * Blends the document accent color onto white at the configured watermark
     * opacity so dompdf (which ignores CSS opacity) still renders a faint,
     * print-safe watermark.
     */
    function document_watermark_color(string $hex = '#EE5D4B', float $opacity = 0.06): string
    {
        $hex = trim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $a = max(0.0, min(1.0, (float) $opacity));

        $blend = static fn (int $c): int => (int) round(255 + ($c - 255) * $a);

        return sprintf('#%02X%02X%02X', $blend($r), $blend($g), $blend($b));
    }
}

if (! function_exists('document_date')) {
    /**
     * Format a date using the configured document date format, e.g. "21 October 2026".
     */
    function document_date(CarbonInterface|string|null $date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $format ??= (string) platform_document_config('date_format');

        return Carbon\Carbon::parse($date)->format($format);
    }
}

if (! function_exists('document_logo_local_path')) {
    /**
     * Resolve the full-colour logo file used on printed documents. A logo
     * uploaded by the super admin (Platform → Documents) wins; otherwise the
     * bundled Kairo CORE transparent logo is used. Returns null when no logo is
     * readable, in which case document templates fall back to a text wordmark.
     */
    function document_logo_local_path(): ?string
    {
        try {
            $uploaded = platform_document_config('logo');
            if (is_array($uploaded)) {
                $uploaded = $uploaded[0] ?? null;
            }
            if (is_string($uploaded) && $uploaded !== '' && Storage::disk('public')->exists($uploaded)) {
                return Storage::disk('public')->path($uploaded);
            }
        } catch (Throwable) {
            // Settings table may not exist during a fresh install.
        }

        $path = public_path('img/kairo-core-logo.png');

        return (is_file($path) && is_readable($path)) ? $path : null;
    }
}

if (! function_exists('document_logo_data_uri')) {
    /**
     * Base64 data-URI of the document logo PNG for use inside dompdf documents,
     * where external/absolute image paths are unreliable. Empty string when no
     * logo exists, so templates can fall back to a text wordmark.
     */
    function document_logo_data_uri(): string
    {
        static $cached;

        if (is_string($cached)) {
            return $cached;
        }

        $path = document_logo_local_path();

        return $cached = ($path !== null)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($path))
            : '';
    }
}

if (! function_exists('document_logo_dimensions')) {
    /**
     * Natural pixel dimensions (width, height) of the configured document logo,
     * so templates can size the logo height first and keep the aspect ratio.
     */
    function document_logo_dimensions(): array
    {
        static $cached;

        if (is_array($cached)) {
            return $cached;
        }

        $path = document_logo_local_path();

        if ($path !== null && ($size = @getimagesize($path)) !== false) {
            return $cached = [(int) $size[0], (int) $size[1]];
        }

        return $cached = [200, 88];
    }
}

if (! function_exists('document_watermark_cache_path')) {
    /**
     * Generate (and cache) a faded watermark PNG server-side from the configured
     * full-colour logo, blending it toward white by the configured opacity so
     * dompdf (which ignores CSS opacity) still prints a faint, print-safe,
     * centred logo. The result is cached by logo + opacity so it is baked once.
     * Returns the file path, or null when the logo cannot be processed.
     */
    function document_watermark_cache_path(): ?string
    {
        $logoPath = document_logo_local_path();
        if ($logoPath === null || ! is_file($logoPath)) {
            return null;
        }

        $opacity = (float) platform_document_config('watermark_opacity', 0.06);
        $opacity = max(0.0, min(1.0, $opacity));

        $dir = storage_path('framework/cache/documents');
        $key = md5((string) realpath($logoPath).'|'.sprintf('%.4F', $opacity));
        $file = $dir.DIRECTORY_SEPARATOR.'watermark-'.$key.'.png';

        if (is_file($file) && is_readable($file)) {
            return $file;
        }

        try {
            if (! extension_loaded('gd')) {
                return null;
            }

            if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                return null;
            }

            $size = @getimagesize($logoPath);
            if ($size === false) {
                return null;
            }
            [$width, $height, $mime] = [(int) $size[0], (int) $size[1], (int) $size[2]];

            $src = match ($mime) {
                IMAGETYPE_PNG => @imagecreatefrompng($logoPath),
                IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
                IMAGETYPE_GIF => @imagecreatefromgif($logoPath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($logoPath) : false,
                default => false,
            };
            if ($src === false) {
                return null;
            }

            // Preserve any transparency in the source logo (the Kairo CORE PNG
            // has a transparent background).
            imagealphablending($src, false);
            imagesavealpha($src, true);

            // Upscale small logos so the watermark still covers a good part of
            // the page without looking pixelated.
            $targetWidth = 600;
            if ($width < $targetWidth) {
                $targetHeight = (int) round($height * ($targetWidth / $width));
                $scaled = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($scaled, false);
                imagesavealpha($scaled, true);
                imagecopyresampled($scaled, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                imagedestroy($src);
                [$src, $width, $height] = [$scaled, $targetWidth, $targetHeight];
            }

            // Composite the (possible transparent) logo onto a white canvas so
            // every pixel has a predictable colour before fading.
            $composite = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($composite, 255, 255, 255);
            imagefill($composite, 0, 0, $white);
            imagealphablending($composite, true);
            imagecopy($composite, $src, 0, 0, 0, 0, $width, $height);
            imagedestroy($src);

            // Fade toward white by (1 - opacity). At 6% opacity the logo looks
            // like a ~6%-opacity layer, which dompdf can render reliably.
            $faded = imagecreatetruecolor($width, $height);
            imagealphablending($faded, false);
            imagesavealpha($faded, true);
            $white = imagecolorallocate($faded, 255, 255, 255);
            imagefill($faded, 0, 0, $white);
            imagealphablending($faded, true);
            imagecopymerge($faded, $composite, 0, 0, 0, 0, $width, $height, (int) round((1 - $opacity) * 100));
            imagedestroy($composite);

            imagepng($faded, $file);
            imagedestroy($faded);
            @chmod($file, 0664);

            return $file;
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('document_watermark_data_uri')) {
    /**
     * Base64 data-URI of the watermark PNG for dompdf documents. A watermark
     * image uploaded by the super admin wins; otherwise the faded watermark is
     * generated from the document logo at the configured opacity.
     */
    function document_watermark_data_uri(): string
    {
        static $cached;

        if (is_string($cached)) {
            return $cached;
        }

        $path = null;

        try {
            $uploaded = platform_document_config('watermark_image');
            if (is_array($uploaded)) {
                $uploaded = $uploaded[0] ?? null;
            }
            if (is_string($uploaded) && $uploaded !== '' && Storage::disk('public')->exists($uploaded)) {
                $path = Storage::disk('public')->path($uploaded);
            }
        } catch (Throwable) {
            $path = null;
        }

        $path ??= document_watermark_cache_path() ?? public_path('img/kairo-core-watermark.png');

        return $cached = (is_file($path) && is_readable($path))
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($path))
            : '';
    }
}

if (! function_exists('brand_email_view_data')) {
    /**
     * Merge resolved branding with per-email content into a ready-to-render
     * payload for resources/views/emails/brand.blade.php.
     */
    function brand_email_view_data(array $overrides = []): array
    {
        return array_merge([
            'logoUrl' => null,
            'companyName' => config('app.name'),
            'companyAddress' => null,
            'companyPhone' => null,
            'companyEmail' => null,
            'heading' => '',
            'greeting' => null,
            'introLines' => [],
            'actionUrl' => null,
            'actionText' => null,
            'outroLines' => [],
            'footerNote' => null,
            'signature' => null,
        ], $overrides);
    }
}

if (! function_exists('tenant_workspace_url')) {
    /**
     * Absolute URL to a workspace path on the SCHOOL's own subdomain.
     *
     * Never use Filament's getUrl() alone for cross-context links: it renders
     * against the current/central host, which sends a tenant user to the
     * central domain where their session cookie does not exist — bouncing them
     * into a login (or worse, the platform panel). This helper pins the host to
     * the school's own subdomain so tenants stay inside their space.
     */
    function tenant_workspace_url(?School $school, string $path = '/'): string
    {
        if ($school === null || blank($school->subdomain)) {
            return url($path);
        }

        return rtrim(school_website_url($school), '/').'/'.ltrim($path, '/');
    }
}

if (! function_exists('fuzzy_regex')) {
    /**
     * Build a MySQL REGEXP pattern that matches an ordered character
     * subsequence, so a typo like "crles" still finds "Charles".
     * Each typed character must appear in order somewhere in the value.
     */
    function fuzzy_regex(?string $term): string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return '.*';
        }

        $parts = preg_split('//u', preg_quote($term, '/'), -1, PREG_SPLIT_NO_EMPTY);

        return ($parts ? implode('.*', $parts) : '.*').'.*';
    }
}

if (! function_exists('fuzzy_search_where')) {
    /**
     * Apply an ordered character subsequence match against one or more columns.
     *
     * @param  Builder  $query
     */
    function fuzzy_search_where($query, array $columns, ?string $term): void
    {
        $pattern = fuzzy_regex($term);

        if ($pattern === '.*') {
            return;
        }

        $query->where(function ($q) use ($columns, $pattern) {
            foreach ($columns as $i => $column) {
                if ($i === 0) {
                    $q->whereRaw("{$column} REGEXP ?", [$pattern]);
                } else {
                    $q->orWhereRaw("{$column} REGEXP ?", [$pattern]);
                }
            }
        });
    }
}

if (! function_exists('inventory_item_search_options')) {
    /**
     * Searchable options for an inventory item picker: fuzzy match on name, sku
     * and item_type and render "Name — Type (Qty on hand)" labels so users can
     * see the item type and its current stock before choosing.
     *
     * @return array<int|string, string>
     */
    function inventory_item_search_options(?string $search, int $limit = 50): array
    {
        $query = InventoryItem::query()
            ->with('category');

        fuzzy_search_where($query, ['name', 'sku', 'item_type'], $search);

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->mapWithKeys(function ($item) {
                $label = $item->name;

                $metaBlock = '';
                if (is_array($item->meta_data) && count($item->meta_data)) {
                    $metaBlock = ' — '.collect($item->meta_data)
                        ->map(fn ($value, $key) => "{$key}: {$value}")
                        ->implode(' | ');
                }

                return [
                    $item->id => $label.' ('.ucfirst((string) $item->item_type).')'
                        .$metaBlock
                        ." — {$item->current_quantity} left",
                ];
            })
            ->all();
    }
}

if (! function_exists('inventory_item_label')) {
    /**
     * Display label for a single selected inventory item (used when a searchable
     * select shows the already-chosen record).
     */
    function inventory_item_label($item): ?string
    {
        if (! $item) {
            return null;
        }

        $label = $item->name.' ('.ucfirst((string) $item->item_type).')';

        if (is_array($item->meta_data) && count($item->meta_data)) {
            $metaBlock = collect($item->meta_data)
                ->map(fn ($value, $key) => "{$key}: {$value}")
                ->implode(' | ');
            $label .= ' — '.$metaBlock;
        }

        return $label;
    }
}
