<?php

namespace App\Services;

use App\Filament\App\Pages\Dashboard as AppDashboard;
use App\Filament\App\Pages\Finance\FeeCollectionsPage;
use App\Filament\App\Pages\Finance\FinancialStatementPage;
use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\SaaSBillingOverview;
use App\Filament\App\Pages\Schedule;
use App\Filament\App\Resources\AcademicReportResource;
use App\Filament\App\Resources\AnnouncementResource;
use App\Filament\App\Resources\ApplicationResource;
use App\Filament\App\Resources\AssessmentMarkResource;
use App\Filament\App\Resources\ClinicVisitResource;
use App\Filament\App\Resources\FeePaymentSubmissionResource;
use App\Filament\App\Resources\HelpdeskTicketResource;
use App\Filament\App\Resources\HostelAllocationResource;
use App\Filament\App\Resources\InvoiceResource;
use App\Filament\App\Resources\LeaveRequestResource;
use App\Filament\App\Resources\StaffAttendanceResource;
use App\Filament\App\Resources\StudentResource;
use App\Filament\App\Resources\SystemAuditLogResource;
use App\Filament\App\Resources\TimetableLessonResource;
use App\Filament\App\Resources\UserAccountResource;
use App\Models\User;
use App\Navigation\ModuleNavigation;
use App\Navigation\ModuleNavigationService;
use App\Security\CapabilityCatalog;
use App\Services\UserRegistrationService;
use App\Support\TinwayChartSvg;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Term;
use Modules\Admissions\Models\Application;
use Modules\Admin\Models\SystemAuditLog;
use Modules\Attendance\Models\StudentAttendance;
use Modules\Clinic\Models\ClinicVisit;
use Modules\Communication\Models\EventCalendar;
use Modules\Communication\Models\HelpdeskTicket;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\Hostels\Models\HostelAllocation;
use Modules\Hostels\Models\HostelBed;
use Modules\Inventory\Models\AssetMaintenanceLog;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource;
use Modules\Inventory\Filament\Resources\InventoryItemResource;
use Modules\Library\Models\LibraryIssue;
use Modules\Library\Filament\Resources\LibraryIssueResource;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimetableLesson;

/**
 * Assembles every figure shown on the Tinway workspace dashboard from live,
 * tenant-scoped records. Models carry the BelongsToTenant global scope, so
 * every query here is already confined to the current school; permission
 * gating (module switch + the person's role) decides which cards render at
 * all, mirroring the rules used by the sidebar and the quick-launch cards of
 * the previous dashboard.
 */
class DashboardMetricsService
{
    /**
     * Every widget's payload, keyed as consumed by the dashboard view.
     */
    public function all(): array
    {
        if (! current_tenant() && ! auth()->user()?->school_id) {
            return [];
        }

        $today = Carbon::today();

        $year = AcademicYear::where('is_active', true)->first();
        $term = $year
            ? Term::where('academic_year_id', $year->id)->where('is_active', true)->first()
            : null;

        $yearStart = $year?->start_date ? Carbon::parse($year->start_date) : $today->copy()->startOfYear();

        $attendance = $this->attendanceCard($today);
        $fees = $this->feesCard($yearStart, $today);
        $marks = $this->marksCard($term, $year);
        $todo = $this->attentionCard();

        return [
            'termLabel' => $year ? trim(($term?->name ? $term->name.' · ' : '').$year->name) : null,
            'nav' => $this->nav(),
            'students' => $this->studentsCard($year),
            'quickActions' => $this->quickActions(),
            'attendance' => $attendance,
            'fees' => $this->feesCard($yearStart, $today),
            'marks' => $marks,
            'funnel' => $this->funnelCard($yearStart),
            'todo' => $todo,
            'schedule' => $this->scheduleCard($today, $year),
            'mini' => $this->miniCard($today),
            'pendingAttendance' => $this->pendingAttendanceCard($today, $year),
            'alerts' => $this->alertsCard($today),
            'feed' => $this->feedCard(),
            'kpis' => $this->kpisCard($today, $attendance, $fees, $marks, $todo, $term),
        ];
    }

    /* ------------------------------------------------------------------
     |  Sidebar
     * ------------------------------------------------------------------ */

    /**
     * Prototype tile colours per module slug, so the Tinway sidebar keeps its
     * designed look while every link, label and permission comes from the
     * real navigation registry.
     */
    protected const NAV_COLORS = [
        'admissions' => '#5b57f0',
        'students' => '#3b87dc',
        'academics' => '#0e9f8a',
        'exams' => '#8b5cf6',
        'finance' => '#e0620f',
        'hr' => '#d63a7c',
        'inventory' => '#a68a0c',
        'library' => '#6a6ee0',
        'boarding' => '#2a9a5b',
        'health' => '#dc4545',
        'communication' => '#0f93c8',
        'lms' => '#7c5ce0',
        'website' => '#b04fc0',
        'reports' => '#3b78b8',
        'administration' => '#5a6578',
        'saas' => '#b07a00',
    ];

    /**
     * Prototype icon key per module slug (keys of the IC set).
     */
    protected const NAV_ICONS = [
        'admissions' => 'Admissions',
        'students' => 'Students',
        'academics' => 'Academics',
        'exams' => 'Exams',
        'finance' => 'Finance',
        'hr' => 'HR',
        'inventory' => 'Inventory',
        'library' => 'Library',
        'boarding' => 'Boarding',
        'health' => 'Health',
        'communication' => 'Comms',
        'lms' => 'LMS',
        'website' => 'Website',
        'reports' => 'Reports',
        'administration' => 'System',
        'saas' => 'Billing',
    ];

    /**
     * The sidebar tree: one group per capability module plus the everyday
     * pages. Labels, links and the permission filter all come from the same
     * navigation registry the Filament sidebar uses, so the two can never
     * disagree; only colours and icons are the designer's.
     */
    protected function nav(): array
    {
        $navigation = app(ModuleNavigationService::class);
        $dashboardUrl = '/'.trim($this->urlFor(AppDashboard::class), '/');

        $everyday = [];

        foreach ([AppDashboard::class, MyDay::class, Schedule::class] as $page) {
            if (! $this->canSee($page)) {
                continue;
            }

            $everyday[] = [
                'label' => $this->pageLabel($page),
                'url' => '/'.trim($this->urlFor($page), '/'),
                'active' => $page === AppDashboard::class,
            ];
        }

        $groups = $everyday === [] ? [] : [
            ['label' => __('Everyday'), 'everyday' => true, 'icon' => 'home', 'color' => null, 'items' => $everyday],
        ];

        foreach (ModuleNavigation::modules() as $module) {
            $slug = $module['slug'];

            $tabs = collect($navigation->moduleTabs($module, true))
                ->map(fn ($tab) => [
                    'label' => $tab['label'],
                    'url' => '/'.trim((string) ($tab['url'] ?? ''), '/'),
                    'group' => $tab['group'] ?? null,
                ])
                ->filter(fn ($tab) => $tab['url'] !== '/')
                ->values();

            if ($tabs->isEmpty()) {
                continue;
            }

            $items = [];

            if ($tabs->pluck('group')->unique()->filter()->count() > 1) {
                // Multiple categories: a collapsible item per category.
                foreach ($tabs->groupBy(fn ($tab) => $tab['group'] ?? '') as $groupTabs) {
                    $items[] = [
                        'label' => (string) $groupTabs->first()['group'],
                        'children' => $groupTabs
                            ->map(fn ($tab) => ['label' => $tab['label'], 'url' => $tab['url']])
                            ->values()
                            ->all(),
                    ];
                }
            } else {
                // One flat list of links.
                $items = $tabs
                    ->map(fn ($tab) => ['label' => $tab['label'], 'url' => $tab['url']])
                    ->values()
                    ->all();
            }

            $groups[] = [
                'label' => $module['label'],
                'slug' => $slug,
                'icon' => self::NAV_ICONS[$slug] ?? 'Reports',
                'color' => self::NAV_COLORS[$slug] ?? '#5a6578',
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * Display label of a universal page without instantiating Filament's
     * navigation; the capability catalogue already carries it.
     */
    protected function pageLabel(string $pageClass): string
    {
        foreach (CapabilityCatalog::universalPages() as $page) {
            if ($page['class'] === $pageClass) {
                return (string) $page['label'];
            }
        }

        return $pageClass;
    }

    /* ------------------------------------------------------------------
     |  Students hero card
     * ------------------------------------------------------------------ */

    protected function studentsCard(?AcademicYear $year): array
    {
        $visible = $this->canSee(StudentResource::class);

        if (! $visible) {
            return ['visible' => false];
        }

        $data = $this->remember('students', function () use ($year) {
            $total = Student::where('status', 'active')->count();
            $girls = Student::where('status', 'active')->where('gender', 'female')->count();

            // A student's class lives on their enrollments, and enrollments
            // are per term, so distinct students are counted, never rows.
            $perCourse = Enrollment::query()
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                ->whereHas('student', fn ($q) => $q->where('status', 'active'))
                ->selectRaw('course_id, COUNT(DISTINCT student_id) AS total')
                ->groupBy('course_id')
                ->pluck('total', 'course_id');

            $grades = Course::query()
                ->when($perCourse->isNotEmpty(), fn ($q) => $q->whereIn('id', $perCourse->keys()))
                ->orderBy('sequence_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($course) => ['label' => $course->name, 'value' => (int) ($perCourse[$course->id] ?? 0)])
                ->values()
                ->all();

            $teachingRoles = array_values(array_unique([
                UserRegistrationService::roleNameForCategory('teaching_staff'),
                'Teaching Staff',
                'Teacher',
                'teacher',
                'teaching_staff',
            ]));

            $teaching = Employee::whereIn('role', $teachingRoles)->whereNull('deleted_at')->count();
            $nonTeaching = Employee::whereNotIn('role', $teachingRoles)->whereNull('deleted_at')->count();

            $admins = User::query()
                ->notPlatformManaged()
                ->where('account_status', 'active')
                ->where(function ($q) {
                    $q->where('requested_role', 'administrator')
                        ->orWhere('requested_role', 'admin')
                        ->orWhereHas('customRole', fn ($role) => $role->where('name', UserRegistrationService::roleNameForCategory('administrator')));
                })
                ->count();

            if ($admins < 1 && auth()->check() && auth()->user()->school_id == (current_tenant()?->id)) {
                $admins = 1;
            }

            return [
                'total' => $total,
                'girls' => $girls,
                'teaching' => $teaching,
                'nonTeaching' => $nonTeaching,
                'admins' => $admins,
                'grades' => $grades,
            ];
        });

        return [
            'visible' => true,
            'total' => $data['total'],
            'girls' => $data['girls'],
            'boys' => max(0, $data['total'] - $data['girls']),
            'grades' => TinwayChartSvg::gradeBars($data['grades'], __('students')),
            'gradeList' => $data['grades'],
            'teaching' => $data['teaching'],
            'nonTeaching' => $data['nonTeaching'],
            'admins' => $data['admins'],
        ];
    }

    /* ------------------------------------------------------------------
     |  Quick actions
     * ------------------------------------------------------------------ */

    protected function quickActions(): array
    {
        $actions = [
            [
                'title' => __('Add a student'),
                'sub' => __('Register or edit profiles'),
                'class' => StudentResource::class,
                'icon' => 'Students',
                'color' => '#5b57f0',
            ],
            [
                'title' => __('Enter marks'),
                'sub' => __('Current term'),
                'class' => AssessmentMarkResource::class,
                'icon' => 'mark',
                'color' => '#8b5cf6',
            ],
            [
                'title' => __('Record a payment'),
                'sub' => __('Fee collections'),
                'class' => FeeCollectionsPage::class,
                'icon' => 'pay',
                'color' => '#18a272',
            ],
            [
                'title' => __('Staff attendance'),
                'sub' => __('Today’s register'),
                'class' => StaffAttendanceResource::class,
                'icon' => 'cal',
                'color' => '#e8920c',
            ],
            [
                'title' => __('Review applications'),
                'sub' => __('Online admissions'),
                'class' => ApplicationResource::class,
                'icon' => 'Admissions',
                'color' => '#3b87dc',
            ],
            [
                'title' => __('Send announcement'),
                'sub' => __('Parents and staff'),
                'class' => AnnouncementResource::class,
                'icon' => 'mega',
                'color' => '#e0508a',
            ],
        ];

        $result = [];

        foreach ($actions as $action) {
            if (! $this->canSee($action['class'])) {
                continue;
            }

            $action['url'] = $this->urlFor($action['class']);
            unset($action['class']);
            $result[] = $action;
        }

        return $result;
    }

    /* ------------------------------------------------------------------
     |  Attendance donut + not-marked list
     * ------------------------------------------------------------------ */

    protected function attendanceCard(Carbon $today): array
    {
        $visible = $this->canSee(TimetableLessonResource::class);

        if (! $visible) {
            return ['visible' => false];
        }

        $counts = $this->remember('attendance.'.$today->toDateString(), function () use ($today) {
            $row = StudentAttendance::whereDate('date', $today)
                ->selectRaw("SUM(status = 'present') present, SUM(status = 'late') late, SUM(status = 'absent') absent, SUM(status = 'excused') excused, COUNT(*) total")
                ->first();

            return [
                'present' => (int) ($row->present ?? 0),
                'late' => (int) ($row->late ?? 0),
                'absent' => (int) ($row->absent ?? 0),
                'excused' => (int) ($row->excused ?? 0),
                'total' => (int) ($row->total ?? 0),
            ];
        });

        $present = $counts['present'];
        $late = $counts['late'];
        $absent = $counts['absent'];
        $excused = $counts['excused'];
        $total = $counts['total'];

        return [
            'visible' => true,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'excused' => $excused,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($present / $total * 100) : null,
            'chart' => TinwayChartSvg::donut([
                ['value' => $present, 'color' => '#18a272', 'label' => __('Present')],
                ['value' => $late, 'color' => '#e8920c', 'label' => __('Late')],
                ['value' => $absent, 'color' => '#dc4545', 'label' => __('Absent')],
                ['value' => $excused, 'color' => '#9aa1c2', 'label' => __('Excused')],
            ], __('Attendance today')),
            'url' => $this->urlFor(TimetableLessonResource::class),
        ];
    }

    protected function pendingAttendanceCard(Carbon $today, ?AcademicYear $year): array
    {
        if (! $this->canSee(TimetableLessonResource::class)) {
            return ['visible' => false];
        }

        $dayName = strtolower($today->englishDayOfWeek);

        $lessons = $this->remember('pending.'.$today->toDateString(), function () use ($dayName, $today, $year) {
            return TimetableLesson::query()
                ->where('day_of_week', $dayName)
                ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                ->with(['section.course', 'section.classTeacher'])
                ->get()
                ->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'section_id' => $lesson->section_id,
                    'class' => $lesson->section?->name ?? '—',
                    'course' => $lesson->section?->course?->name ?? '',
                    'teacher' => $lesson->section?->classTeacher?->name,
                ])
                ->all();
        });

        if ($lessons === []) {
            return ['visible' => true, 'empty' => true, 'rows' => []];
        }

        $markedLessonIds = StudentAttendance::whereDate('date', $today)
            ->whereNotNull('timetable_lesson_id')
            ->distinct()
            ->pluck('timetable_lesson_id');

        $sections = collect($lessons)->groupBy('section_id')->map(function ($sectionLessons) use ($markedLessonIds) {
            $first = $sectionLessons->first();

            return [
                'class' => $first['class'],
                'course' => $first['course'],
                'teacher' => $first['teacher'],
                'total' => $sectionLessons->count(),
                'marked' => collect($sectionLessons)->whereIn('id', $markedLessonIds->all())->count(),
            ];
        });

        $rows = $sections->map(function ($row) {
            $row['status'] = $row['marked'] === 0 ? 'none' : ($row['marked'] < $row['total'] ? 'partial' : 'marked');

            return $row;
        })
            ->sortBy(fn ($row) => ['none' => 0, 'partial' => 1, 'marked' => 2][$row['status']])
            ->take(5)
            ->values()
            ->all();

        return ['visible' => true, 'empty' => false, 'rows' => $rows];
    }

    /* ------------------------------------------------------------------
     |  Fees
     * ------------------------------------------------------------------ */

    protected function feesCard(Carbon $yearStart, Carbon $today): array
    {
        if (! $this->canSee(InvoiceResource::class)) {
            return ['visible' => false];
        }

        $data = $this->remember('fees.'.$yearStart->toDateString().'.'.$today->toDateString(), function () use ($yearStart, $today) {
            $collectedQuery = Payment::query()
                ->where(fn ($q) => $q->where('is_refund', false)->orWhereNull('is_refund'))
                ->where('is_reversed', false)
                ->whereDoesntHave('invoice', fn ($q) => $q->whereNotNull('carried_forward_at'))
                ->whereDate('payment_date', '>=', $yearStart)
                ->whereDate('payment_date', '<=', $today);

            $collected = (float) $collectedQuery->clone()->sum('amount');
            $billed = (float) Invoice::query()
                ->whereDate('created_at', '>=', $yearStart)
                ->sum('total_amount');
            $outstanding = (float) Invoice::query()
                ->where('status', '!=', 'paid')
                ->sum('balance_amount');

            $monthly = $collectedQuery->clone()
                ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') ym, SUM(amount) total")
                ->whereNotNull('payment_date')
                ->groupBy('ym')
                ->orderBy('ym')
                ->pluck('total', 'ym');

            $totalSeries = [];
            $monthlySeries = [];
            $cumulative = 0.0;
            $cursor = $yearStart->copy()->startOfMonth();
            $now = Carbon::now();

            while ($cursor->lessThanOrEqualTo($now)) {
                $key = $cursor->format('Y-m');
                $value = round((float) ($monthly[$key] ?? 0), 2);
                $cumulative += $value;
                $label = $cursor->locale(app()->getLocale())->translatedFormat('M');

                $totalSeries[] = [
                    'label' => $label,
                    'value' => $cumulative,
                    'tip' => $label.' · '.$this->money($cumulative),
                ];
                $monthlySeries[] = [
                    'label' => $label,
                    'value' => $value,
                    'tip' => $label.' · '.$this->money($value),
                ];

                $cursor->addMonth();
            }

            return [
                'collected' => $collected,
                'billed' => $billed,
                'outstanding' => $outstanding,
                'totalSeries' => $totalSeries,
                'monthlySeries' => $monthlySeries,
            ];
        });

        $points = $data['totalSeries'];
        $monthlyPoints = $data['monthlySeries'];

        $last = count($monthlyPoints) - 1;

        if ($last >= 0) {
            $points[$last]['last'] = $this->moneyCompact($points[$last]['value']);
            $monthlyPoints[$last]['last'] = $this->moneyCompact($monthlyPoints[$last]['value']);
        }

        return [
            'visible' => true,
            'collected' => $data['collected'],
            'billed' => $data['billed'],
            'outstanding' => $data['outstanding'],
            'percent' => $data['billed'] > 0 ? (int) round($data['collected'] / $data['billed'] * 100) : 0,
            'totalChart' => TinwayChartSvg::areaChart($points, 'total', __('Fees collected this year, cumulative')),
            'monthlyChart' => TinwayChartSvg::areaChart($monthlyPoints, 'monthly', __('Fees collected per month')),
            'hasChart' => count($points) >= 2,
            'statementsUrl' => $this->urlFor(FinancialStatementPage::class),
            'collectionsUrl' => $this->urlFor(FeeCollectionsPage::class),
        ];
    }

    /* ------------------------------------------------------------------
     |  Marks by subject
     * ------------------------------------------------------------------ */

    /**
     * Marks belong to a term through their assessment type (types without a
     * term apply to every term) and to a year through the enrollment, exactly
     * how the analytics page and the result calculator scope them.
     */
    protected function marksCard(?Term $term, ?AcademicYear $year): array
    {
        if (! $this->canSee(AssessmentMarkResource::class)) {
            return ['visible' => false];
        }

        if ($term) {
            $data = $this->remember('marks.'.$term->id, function () use ($term, $year) {
                $typeIds = AssessmentType::query()
                    ->where(fn ($q) => $q->where('term_id', $term->id)->orWhereNull('term_id'))
                    ->pluck('id');

                $markQuery = AssessmentMark::query()
                    ->whereNotNull('marks_obtained')
                    ->whereIn('assessment_type_id', $typeIds)
                    ->whereHas('enrollment', fn ($q) => $q
                        ->where('academic_year_id', $year?->id ?? $term->academic_year_id)
                        ->where('status', Enrollment::STATUS_ACTIVE));

                $currentAvg = (clone $markQuery)->avg('marks_obtained');

                $subjects = (clone $markQuery)
                    ->join('subjects', 'subjects.id', '=', 'assessment_marks.subject_id')
                    ->groupBy('subjects.id', 'subjects.name')
                    ->selectRaw('subjects.id id, subjects.name name, ROUND(AVG(assessment_marks.marks_obtained), 0) average')
                    ->orderByDesc('average')
                    ->limit(6)
                    ->get()
                    ->map(fn ($s) => ['name' => $s->name, 'average' => (int) $s->average])
                    ->all();

                $previousAvg = null;
                $previousTerm = Term::query()
                    ->where('academic_year_id', $term->academic_year_id)
                    ->where('start_date', '<', $term->start_date)
                    ->orderByDesc('start_date')
                    ->first();

                if ($previousTerm) {
                    $previousTypeIds = AssessmentType::query()
                        ->where(fn ($q) => $q->where('term_id', $previousTerm->id)->orWhereNull('term_id'))
                        ->pluck('id');

                    $previousAvg = AssessmentMark::query()
                        ->whereNotNull('marks_obtained')
                        ->whereIn('assessment_type_id', $previousTypeIds)
                        ->whereHas('enrollment', fn ($q) => $q
                            ->where('academic_year_id', $year?->id ?? $term->academic_year_id)
                            ->where('status', Enrollment::STATUS_ACTIVE))
                        ->avg('marks_obtained');
                }

                return [
                    'average' => $currentAvg !== null ? (int) round((float) $currentAvg) : null,
                    'delta' => ($currentAvg !== null && $previousAvg !== null)
                        ? (int) round((float) $currentAvg - (float) $previousAvg)
                        : null,
                    'subjects' => $subjects,
                ];
            });

            return [
                'visible' => true,
                'average' => $data['average'],
                'delta' => $data['delta'],
                'subjects' => $data['subjects'],
                'url' => $this->urlFor(AssessmentMarkResource::class),
            ];
        }

        return [
            'visible' => true,
            'average' => null,
            'delta' => null,
            'subjects' => [],
            'url' => $this->urlFor(AssessmentMarkResource::class),
        ];
    }

    /* ------------------------------------------------------------------
     |  Admissions funnel
     * ------------------------------------------------------------------ */

    protected function funnelCard(Carbon $yearStart): array
    {
        if (! $this->canSee(ApplicationResource::class)) {
            return ['visible' => false];
        }

        $counts = $this->remember('funnel.'.$yearStart->toDateString(), function () use ($yearStart) {
            $applications = (int) Application::query()->whereDate('created_at', '>=', $yearStart)->count();
            $verified = (int) Application::query()
                ->whereDate('created_at', '>=', $yearStart)
                ->where(function ($q) {
                    $q->whereIn('status', ['verified', 'confirmed', 'enrolled'])
                        ->orWhere('documents_verified', true);
                })
                ->count();
            $enrolled = (int) Application::query()
                ->whereDate('created_at', '>=', $yearStart)
                ->where('status', 'enrolled')
                ->count();

            return ['applications' => $applications, 'verified' => $verified, 'enrolled' => $enrolled];
        });

        return [
            'visible' => true,
            'applications' => $counts['applications'],
            'verified' => $counts['verified'],
            'enrolled' => $counts['enrolled'],
            'url' => $this->urlFor(ApplicationResource::class),
        ];
    }

    /* ------------------------------------------------------------------
     |  Needs your attention
     * ------------------------------------------------------------------ */

    protected function attentionCard(): array
    {
        $items = [
            [
                'class' => ApplicationResource::class,
                'key' => 'applications',
                'count' => fn () => (int) Application::query()->where('status', 'pending')->count(),
                'label' => __('Applications waiting for review'),
                'area' => __('Online Admissions'),
                'verb' => __('Review'),
            ],
            [
                'class' => FeePaymentSubmissionResource::class,
                'key' => 'payment_proofs',
                'count' => fn () => (int) StudentPaymentSubmission::query()->where('status', StudentPaymentSubmission::STATUS_PENDING)->count(),
                'label' => __('Payment proofs to verify'),
                'area' => __('Finance'),
                'verb' => __('Verify'),
            ],
            [
                'class' => LeaveRequestResource::class,
                'key' => 'leave_requests',
                'count' => fn () => (int) LeaveRequest::query()->where('status', 'pending')->count(),
                'label' => __('Leave requests to approve'),
                'area' => __('HR & Payroll'),
                'verb' => __('Approve'),
            ],
            [
                'class' => AcademicReportResource::class,
                // Reports carry no published_at stamp: "approved" is the
                // ready-to-publish state, "published" means already live.
                'key' => 'reports',
                'count' => fn () => (int) AcademicReport::query()->where('status', 'approved')->count(),
                'label' => __('Report cards ready to publish'),
                'area' => __('Exams & Grading'),
                'verb' => __('Publish'),
            ],
        ];

        // The counts are cached for everyone in the tenant; which rows a
        // given person may act on is resolved fresh below.
        $counts = $this->remember('attention', fn () => collect($items)
            ->mapWithKeys(fn ($item) => [$item['key'] => (int) $item['count']()])
            ->all());

        $rows = [];
        $anyVisible = false;

        foreach ($items as $item) {
            if (! $this->canSee($item['class'])) {
                continue;
            }

            $anyVisible = true;
            $count = (int) ($counts[$item['key']] ?? 0);

            if ($count < 1) {
                continue;
            }

            $rows[] = [
                'count' => $count,
                'label' => $item['label'],
                'area' => $item['area'],
                'verb' => $item['verb'],
                'url' => $this->urlFor($item['class']),
            ];
        }

        return ['visible' => $anyVisible, 'rows' => $rows];
    }

    /* ------------------------------------------------------------------
     |  Today's schedule
     * ------------------------------------------------------------------ */

    protected function scheduleCard(Carbon $today, ?AcademicYear $year): array
    {
        $visible = $this->canSee(Schedule::class);

        if (! $visible) {
            return ['visible' => false];
        }

        $entries = $this->remember('schedule.'.$today->toDateString(), function () use ($today, $year) {
            $entries = [];

            $events = EventCalendar::query()
                ->whereDate('start_time', $today)
                ->orderBy('start_time')
                ->limit(6)
                ->get();

            foreach ($events as $event) {
                $entries[] = [
                    'time' => $event->start_time?->format('H:i') ?? '--:--',
                    'title' => $event->title,
                    'sub' => trim(($event->location ?: '').($event->location && $event->category ? ' · ' : '').(EventCalendar::CATEGORIES[$event->category] ?? '')),
                ];
            }

            $dayName = strtolower($today->englishDayOfWeek);

            $lessons = TimetableLesson::query()
                ->where('day_of_week', $dayName)
                ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                ->with(['timeSlot', 'subject', 'section.course'])
                ->get()
                ->sortBy(fn ($lesson) => $lesson->timeSlot?->start_time ?? '99:99');

            foreach ($lessons as $lesson) {
                $entries[] = [
                    'time' => $lesson->timeSlot?->start_time
                        ? Carbon::parse($lesson->timeSlot->start_time)->format('H:i')
                        : '--:--',
                    'title' => trim(($lesson->subject?->name ?: __('Lesson')).' · '.($lesson->section?->name ?: '')),
                    'sub' => (string) ($lesson->section?->course?->name ?? ''),
                ];
            }

            return collect($entries)->sortBy('time')->take(6)->values()->all();
        });

        return [
            'visible' => true,
            'rows' => $entries,
            'url' => $this->urlFor(Schedule::class),
        ];
    }

    /* ------------------------------------------------------------------
     |  This week across the school
     * ------------------------------------------------------------------ */

    protected function miniCard(Carbon $today): array
    {
        $dateKey = $today->toDateString();

        $books = ['visible' => false];

        if ($this->canSee(LibraryIssueResource::class)) {
            $data = $this->remember('mini.books.'.$dateKey, fn () => [
                'on_loan' => (int) LibraryIssue::query()->whereIn('status', ['issued', 'overdue'])->count(),
                'week' => (int) LibraryIssue::query()->where('issued_at', '>=', $today->copy()->startOfWeek())->count(),
                'series' => $this->dailySeries(LibraryIssue::query(), 'issued_at', $today),
            ]);

            $books = [
                'visible' => true,
                'on_loan' => $data['on_loan'],
                'week' => $data['week'],
                'spark' => TinwayChartSvg::sparkline($data['series']),
            ];
        }

        $clinic = ['visible' => false];

        if ($this->canSee(ClinicVisitResource::class)) {
            $data = $this->remember('mini.clinic.'.$dateKey, function () use ($today) {
                $weekStart = $today->copy()->startOfWeek();

                return [
                    'week' => (int) ClinicVisit::query()->where('visit_time', '>=', $weekStart)->count(),
                    'prev' => (int) ClinicVisit::query()->whereBetween('visit_time', [$weekStart->copy()->subWeek(), $weekStart])->count(),
                    'series' => $this->dailySeries(ClinicVisit::query(), 'visit_time', $today),
                ];
            });

            $clinic = [
                'visible' => true,
                'week' => $data['week'],
                'delta' => $data['week'] - $data['prev'],
                'spark' => TinwayChartSvg::sparkline($data['series']),
            ];
        }

        $boarders = ['visible' => false];

        if ($this->canSee(HostelAllocationResource::class)) {
            $data = $this->remember('mini.boarders.'.$dateKey, fn () => [
                'active' => (int) HostelAllocation::query()->where('status', 'active')->count(),
                'beds' => (int) HostelBed::query()->count(),
                'series' => $this->dailySeries(HostelAllocation::query(), 'allocated_at', $today),
            ]);

            $boarders = [
                'visible' => true,
                'active' => $data['active'],
                'beds' => $data['beds'],
                'spark' => TinwayChartSvg::sparkline($data['series']),
            ];
        }

        $tickets = ['visible' => false];

        if ($this->canSee(HelpdeskTicketResource::class)) {
            $data = $this->remember('mini.tickets.'.$dateKey, fn () => [
                'open' => (int) HelpdeskTicket::query()->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                'closed_today' => (int) HelpdeskTicket::query()->whereIn('status', ['resolved', 'closed'])->whereDate('resolved_at', $today)->count(),
                'series' => $this->dailySeries(HelpdeskTicket::query(), 'created_at', $today),
            ]);

            $tickets = [
                'visible' => true,
                'open' => $data['open'],
                'closed_today' => $data['closed_today'],
                'spark' => TinwayChartSvg::sparkline($data['series']),
            ];
        }

        return ['books' => $books, 'clinic' => $clinic, 'boarders' => $boarders, 'tickets' => $tickets];
    }

    /* ------------------------------------------------------------------
     |  Alerts and school health
     * ------------------------------------------------------------------ */

    protected function alertsCard(Carbon $today): array
    {
        $dateKey = $today->toDateString();

        // Raw counts, cached per tenant; tone, links and the permission
        // filter are applied fresh below.
        $counts = $this->remember('alerts.'.$dateKey, function () use ($today) {
            $subscription = SaaSSubscription::query()
                ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id)
                ->first();

            $subscriptionDays = null;
            $subscriptionStatus = null;

            if ($subscription) {
                $until = $subscription->status === 'trialing'
                    ? ($subscription->trial_ends_at ?: $subscription->ends_at)
                    : ($subscription->next_payment_date ?: $subscription->ends_at);

                if ($until) {
                    $subscriptionDays = (int) Carbon::parse($until)->startOfDay()->diffInDays($today);
                    $subscriptionStatus = $subscription->status;
                }
            }

            return [
                'overdue' => (int) LibraryIssue::query()
                    ->whereIn('status', ['issued', 'overdue'])
                    ->whereNull('returned_at')
                    ->where('due_at', '<', $today)
                    ->count(),
                'low_stock' => (int) InventoryItem::query()
                    ->whereColumn('current_quantity', '<=', 'reorder_level')
                    ->count(),
                'maintenance' => (int) AssetMaintenanceLog::query()
                    ->whereNull('completed_date')
                    ->whereBetween('scheduled_date', [$today, $today->copy()->addDays(7)])
                    ->count(),
                'pending_users' => (int) User::query()->where('account_status', 'pending')->count(),
                'subscription_days' => $subscriptionDays,
                'subscription_status' => $subscriptionStatus,
            ];
        });

        $rows = [];

        if ($counts['overdue'] > 0 && $this->canSee(LibraryIssueResource::class)) {
            $rows[] = [
                'area' => __('Library'),
                'text' => __(':count books overdue', ['count' => $counts['overdue']]),
                'tone' => 's-w',
                'action' => __('Review'),
                'url' => $this->urlFor(LibraryIssueResource::class),
            ];
        }

        if ($counts['low_stock'] > 0 && $this->canSee(InventoryItemResource::class)) {
            $rows[] = [
                'area' => __('Inventory'),
                'text' => __(':count items at or below reorder level', ['count' => $counts['low_stock']]),
                'tone' => 's-w',
                'action' => __('Reorder'),
                'url' => $this->urlFor(InventoryItemResource::class),
            ];
        }

        if ($counts['maintenance'] > 0 && $this->canSee(AssetMaintenanceResource::class)) {
            $rows[] = [
                'area' => __('Fixed assets'),
                'text' => __(':count maintenance jobs due this week', ['count' => $counts['maintenance']]),
                'tone' => 's-w',
                'action' => __('View'),
                'url' => $this->urlFor(AssetMaintenanceResource::class),
            ];
        }

        if ($counts['pending_users'] > 0 && $this->canSee(UserAccountResource::class)) {
            $rows[] = [
                'area' => __('Accounts'),
                'text' => __(':count registrations awaiting approval', ['count' => $counts['pending_users']]),
                'tone' => 's-w',
                'action' => __('Approve'),
                'url' => $this->urlFor(UserAccountResource::class),
            ];
        }

        if ($counts['subscription_days'] !== null && $this->canSee(SaaSBillingOverview::class)) {
            $days = (int) $counts['subscription_days'];

            $rows[] = [
                'area' => __('Subscription'),
                'text' => $days > 0
                    ? __(':status · renews in :days days', ['status' => ucfirst((string) $counts['subscription_status']), 'days' => $days])
                    : __(':status · renewal overdue', ['status' => ucfirst((string) $counts['subscription_status'])]),
                'tone' => $days <= 14 ? 's-w' : 's-o',
                'action' => __('Manage'),
                'url' => $this->urlFor(SaaSBillingOverview::class),
            ];
        }

        return ['rows' => $rows];
    }

    /* ------------------------------------------------------------------
     |  Recent activity feed
     * ------------------------------------------------------------------ */

    protected function feedCard(): array
    {
        if (! $this->canSee(SystemAuditLogResource::class)) {
            return ['visible' => false];
        }

        $logs = $this->remember('feed', fn () => SystemAuditLog::query()
            ->with('user')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn ($log) => [
                'text' => $log->action,
                'sub' => trim(($log->user?->name ?: '').($log->user && $log->module ? ' · ' : '').($log->module ?: '')),
                'time' => $log->created_at?->diffForHumans(),
            ])
            ->all());

        return [
            'visible' => true,
            'rows' => $logs,
            'url' => $this->urlFor(SystemAuditLogResource::class),
        ];
    }

    /* ------------------------------------------------------------------
     |  Top KPI row
     * ------------------------------------------------------------------ */

    protected function kpisCard(Carbon $today, array $attendance, array $fees, array $marks, array $todo, ?Term $term): array
    {
        $kpis = [];

        if ($attendance['visible'] && $attendance['total'] > 0) {
            $pastRate = $this->remember('attendance.past.'.$today->toDateString(), function () use ($today) {
                $past = StudentAttendance::query()
                    ->whereBetween('date', [$today->copy()->subDays(7), $today->copy()->subDay()])
                    ->selectRaw("SUM(status = 'present') present, COUNT(*) total")
                    ->first();

                return (int) $past->total > 0 ? (int) round((int) $past->present / (int) $past->total * 100) : null;
            });

            $kpis[] = [
                'label' => __('Attendance today'),
                'value' => $attendance['percent'],
                'suffix' => '%',
                'delta' => $pastRate !== null ? $attendance['percent'] - $pastRate : null,
                'deltaLabel' => __('vs last week'),
                'note' => __(':count students marked', ['count' => $attendance['total']]),
                'noteTone' => 'up',
                'color' => '#18a272',
                'icon' => 'cal',
            ];
        }

        if ($fees['visible']) {
            $kpis[] = [
                'label' => __('Fees collected'),
                'value' => $fees['percent'],
                'suffix' => '%',
                'delta' => null,
                'deltaLabel' => null,
                'note' => $this->money($fees['outstanding']),
                'noteTone' => 'dn',
                'color' => '#5b57f0',
                'icon' => 'pay',
            ];
        }

        if ($marks['visible'] && $marks['average'] !== null) {
            $kpis[] = [
                'label' => __('Average mark'),
                'value' => $marks['average'],
                'suffix' => '%',
                'delta' => $marks['delta'],
                'deltaLabel' => __('vs last term'),
                'note' => $term?->name ?? '',
                'noteTone' => 'up',
                'color' => '#8b5cf6',
                'icon' => 'mark',
            ];
        }

        $awaiting = array_sum(array_column($todo['rows'], 'count'));

        if ($todo['rows'] !== []) {
            $overdue = (int) $this->remember('attention.overdue', fn () => StudentPaymentSubmission::query()
                ->where('status', StudentPaymentSubmission::STATUS_PENDING)
                ->whereDate('created_at', '<', $today->copy()->subDays(7))
                ->count());

            $kpis[] = [
                'label' => __('Awaiting approval'),
                'value' => $awaiting,
                'suffix' => '',
                'delta' => null,
                'deltaLabel' => null,
                'note' => $overdue > 0
                    ? __(':count overdue', ['count' => $overdue])
                    : __('All within SLA'),
                'noteTone' => $overdue > 0 ? 'dn' : 'up',
                'color' => '#e8920c',
                'icon' => 'exams',
            ];
        }

        return $kpis;
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------ */

    /**
     * May this person see the given Filament Resource or Page? Combines the
     * tenant's module switch with the role's own capability, exactly like the
     * sidebar does, so a card never advertises a page that would 403.
     */
    protected function canSee(string $class): bool
    {
        try {
            if (! \App\Services\ModuleVisibilityManager::isResourceVisible($class)) {
                return false;
            }

            if (is_subclass_of($class, Resource::class) || is_subclass_of($class, Page::class)) {
                return (bool) $class::canAccess();
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Short-lived per-tenant cache for the widget figures. The counts are
     * identical for everyone in the school, so one shared snapshot absorbs
     * the traffic of a busy morning; permission and visibility checks never
     * pass through here, so a role change shows up immediately.
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     */
    protected function remember(string $key, callable $fn)
    {
        $tenant = current_tenant()?->id ?? auth()->user()?->school_id ?? 0;

        return Cache::remember('tinway.dash.'.$tenant.'.'.$key, 60, $fn);
    }

    /**
     * Landing URL for a Resource or Page class, or '#' when unresolvable.
     */
    protected function urlFor(string $class): string
    {
        try {
            if (is_subclass_of($class, Resource::class)) {
                return $class::getUrl('index');
            }

            if (is_subclass_of($class, Page::class)) {
                return $class::getUrl();
            }
        } catch (\Throwable $e) {
            return '#';
        }

        return '#';
    }

    protected function money(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    protected function moneyCompact(float $value): string
    {
        if (abs($value) >= 1000) {
            return '$'.number_format($value / 1000, 1).'k';
        }

        return '$'.number_format($value, 0);
    }

    /**
     * Seven daily counts ending today, feeding a sparkline. One grouped
     * query per model instead of one query per day.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array<int, int>
     */
    protected function dailySeries($query, string $column, Carbon $today): array
    {
        $from = $today->copy()->subDays(6)->startOfDay();
        $to = $today->copy()->endOfDay();

        $rows = $query
            ->whereBetween($column, [$from, $to])
            ->selectRaw("DATE({$column}) day, COUNT(*) total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $values[] = (int) ($rows[$today->copy()->subDays($i)->toDateString()] ?? 0);
        }

        return $values;
    }
}
