@extends('marketing.layout')

@section('favicon', asset('images/kairocore-icon.png'))
@section('title', platform_name().' Preparing Demo')
@section('meta_description', marketing_text('Your private demo environment is being prepared.'))

@php
    $slides = [
        [
            'id' => 'roles',
            'kicker' => marketing_text('Role switcher'),
            'title' => marketing_text('Change roles without signing out.'),
            'body' => marketing_text('Explore the same school as an administrator, teacher, student, accountant and more from the topbar.'),
            'chips' => [__('Administrator'), __('Teacher'), __('Student'), __('Accountant')],
            'stat' => __('6 roles'),
            'stat_label' => __('one session'),
            'bars' => [54, 72, 63, 88],
        ],
        [
            'id' => 'attendance',
            'kicker' => marketing_text('Attendance'),
            'title' => marketing_text('Mark a whole class in seconds.'),
            'body' => marketing_text('Daily registers, late arrivals and absentee follow-ups stay together in one clear view.'),
            'chips' => [__('Today'), __('Late'), __('Absent'), __('Excused')],
            'stat' => __('100%'),
            'stat_label' => __('class coverage'),
            'bars' => [44, 68, 52, 84],
        ],
        [
            'id' => 'billing',
            'kicker' => marketing_text('Fees and billing'),
            'title' => marketing_text('See every invoice and payment clearly.'),
            'body' => marketing_text('Issue invoices, record payments and follow balances without leaving the dashboard.'),
            'chips' => [__('Invoices'), __('Payments'), __('Balances'), __('Receipts')],
            'stat' => __('Live'),
            'stat_label' => __('fee ledger'),
            'bars' => [62, 78, 71, 92],
        ],
        [
            'id' => 'reports',
            'kicker' => marketing_text('Assessments'),
            'title' => marketing_text('Turn class results into report cards.'),
            'body' => marketing_text('Continuous assessment marks become polished report cards with comments and summaries.'),
            'chips' => [__('Tests'), __('Exams'), __('Grades'), __('Remarks')],
            'stat' => __('Auto'),
            'stat_label' => __('report cards'),
            'bars' => [58, 74, 80, 90],
        ],
        [
            'id' => 'announcements',
            'kicker' => marketing_text('Announcements'),
            'title' => marketing_text('Reach the right people instantly.'),
            'body' => marketing_text('Send an update to a class, a role or the whole school from one simple composer.'),
            'chips' => [__('Parents'), __('Staff'), __('Students'), __('Classes')],
            'stat' => __('1 to many'),
            'stat_label' => __('targeted updates'),
            'bars' => [48, 66, 76, 86],
        ],
    ];
@endphp

@push('styles')
<style>
    .dp-panel {
        box-shadow:
            0 0 0 1px rgba(15, 23, 42, 0.04),
            0 1px 2px 0 rgba(15, 23, 42, 0.04),
            0 24px 64px -28px rgba(217, 75, 57, 0.35);
    }

    html.dark .dp-panel {
        box-shadow:
            0 0 0 1px rgba(148, 163, 184, 0.08),
            0 24px 64px -28px rgba(0, 0, 0, 0.8);
    }

    .dp-slide-enter-active,
    .dp-slide-leave-active {
        transition-property: opacity, transform, filter;
        transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform, filter;
    }

    .dp-slide-enter-active { transition-duration: 460ms; }
    .dp-slide-leave-active { transition-duration: 220ms; transition-timing-function: cubic-bezier(0.4, 0, 1, 1); }
    .dp-slide-enter-from { opacity: 0; transform: translateY(10px) scale(0.985); filter: blur(4px); }
    .dp-slide-enter-to { opacity: 1; transform: none; filter: blur(0); }
    .dp-slide-leave-from { opacity: 1; transform: none; filter: blur(0); }
    .dp-slide-leave-to { opacity: 0; transform: translateY(-6px) scale(0.99); filter: blur(2px); }
</style>
@endpush

@section('content')
<section class="relative overflow-hidden bg-white dark:bg-[#0B1120] transition-colors duration-200">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-[#D94B39]/8 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-indigo-500/8 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[0.35] dark:opacity-20" style="background-image: radial-gradient(circle at 1px 1px, rgba(100,116,139,0.18) 1px, transparent 0); background-size: 28px 28px;"></div>
    </div>

    <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-center lg:py-16"
         x-data="{
             slides: @js($slides),
             index: 0,
             attempts: 0,
             busy: false,
             failed: false,
             slideTimer: null,
             reduceMotion: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
             init() {
                 this.shuffleSlides();
                 this.index = Math.floor(Math.random() * this.slides.length);
                 if (!this.reduceMotion) {
                     this.slideTimer = window.setInterval(() => this.advance(), 4200);
                 }
                 this.poll();
             },
             shuffleSlides() {
                 for (let i = this.slides.length - 1; i > 0; i--) {
                     const j = Math.floor(Math.random() * (i + 1));
                     [this.slides[i], this.slides[j]] = [this.slides[j], this.slides[i]];
                 }
             },
             advance() {
                 this.index = (this.index + 1) % this.slides.length;
             },
             step(amount) {
                 this.go((this.index + amount + this.slides.length) % this.slides.length);
             },
             go(index) {
                 this.index = index;
                 if (this.slideTimer) window.clearInterval(this.slideTimer);
                 if (!this.reduceMotion) {
                     this.slideTimer = window.setInterval(() => this.advance(), 4200);
                 }
             },
             async poll() {
                 if (this.busy || this.failed) return;
                 this.busy = true;
                 this.attempts++;
                 try {
                     const response = await fetch(@js(route('demo.preparing.status')), {
                         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                     });
                     const payload = await response.json();
                     if (payload.status === 'ready' && payload.url) {
                         window.location.assign(payload.url);
                         return;
                     }
                     if (payload.status === 'failed' || payload.status === 'expired') {
                         this.failed = true;
                         window.location.assign(payload.url || @js(route('demo.login')));
                         return;
                     }
                 } catch (error) {
                     if (this.attempts > 10) this.failed = true;
                 } finally {
                     this.busy = false;
                 }
                 if (!this.failed) window.setTimeout(() => this.poll(), 1500);
             }
          }"
          x-cloak
         aria-busy="true">

        <div>
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/kairocore-icon.png') }}" alt="" class="h-11 w-11 rounded-2xl shadow-sm">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-[#D94B39] dark:text-[#EF5F4D]">
                        {{ marketing_text('Kairo CORE demo') }}
                    </p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ marketing_text('A private space to explore') }}</p>
                </div>
            </div>

            <h1 class="mt-8 text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                {{ marketing_text('Preparing your demo') }}
            </h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                {{ marketing_text('We are setting up an isolated school just for this session. This usually takes a few seconds.') }}
            </p>

            <ol class="mt-8 space-y-3">
                <li class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#D94B39]/12 text-[#D94B39] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    {{ marketing_text('Create your isolated school') }}
                </li>
                <li class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#D94B39]/12 text-[#D94B39] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    {{ marketing_text('Load realistic sample data') }}
                </li>
                <li class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    </span>
                    {{ marketing_text('Open your workspace') }}
                </li>
            </ol>

            <div class="mt-8 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                 role="progressbar"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 :aria-valuenow="Math.min(12 + attempts * 9, 92)">
                <div class="h-full w-full origin-left rounded-full bg-gradient-to-r from-[#D94B39] to-[#EF5F4D] transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]"
                     :style="`transform: scaleX(${Math.min(0.12 + attempts * 0.09, 0.92)})`"></div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-400" role="status" aria-live="polite"
               x-text="failed ? @js(__('Setup paused. Please try again.')) : @js(__('Please keep this page open'))"></p>
            <a href="{{ route('demo.login') }}" x-show="failed" x-cloak class="mt-3 inline-flex text-sm font-bold text-[#D94B39] underline decoration-2 underline-offset-4 hover:text-[#c14332] dark:text-[#EF5F4D]">
                {{ marketing_text('Try again') }}
            </a>
        </div>

        <div class="dp-panel relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/85 p-6 backdrop-blur-sm dark:border-slate-800 dark:bg-[#0F172A]/85 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#D94B39]/10 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-[#B93A2B] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#D94B39] text-white dark:bg-[#EF5F4D]">
                        <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    {{ marketing_text('Did you know?') }}
                </span>
                <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400"
                      x-text="String(index + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0')"></span>
            </div>

            <div class="relative mt-6 min-h-[22rem] sm:min-h-[20rem]">
                <template x-for="(item, i) in slides" :key="item.id">
                    <article x-show="i === index" x-cloak
                             x-transition:enter="dp-slide-enter-active"
                             x-transition:enter-start="dp-slide-enter-from"
                             x-transition:enter-end="dp-slide-enter-to"
                             x-transition:leave="dp-slide-leave-active"
                             x-transition:leave-start="dp-slide-leave-from"
                             x-transition:leave-end="dp-slide-leave-to"
                             class="absolute inset-0 flex flex-col">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400" x-text="item.kicker"></p>
                            <h2 class="mt-2 text-2xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-3xl" x-text="item.title"></h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="item.body"></p>
                        </div>

                        <div class="mt-auto overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-[#0B1324]/80">
                            <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#D94B39]/80"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80"></span>
                                <span class="ml-2 text-[11px] font-semibold text-slate-400">kairocore / workspace</span>
                            </div>
                            <div class="grid gap-4 p-4 sm:grid-cols-[0.9fr_1.1fr]">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="item.stat_label"></p>
                                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="item.stat"></p>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <template x-for="chip in item.chips" :key="chip">
                                            <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" x-text="chip"></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex h-24 items-end gap-2" aria-hidden="true">
                                    <template x-for="(bar, barIndex) in item.bars" :key="barIndex">
                                        <span class="flex-1 rounded-t-md bg-gradient-to-t from-[#D94B39]/25 to-[#D94B39] dark:from-[#EF5F4D]/20 dark:to-[#EF5F4D]" :style="`height: ${bar}%`"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-200 pt-5 dark:border-slate-800">
                <div class="flex items-center gap-2" aria-label="{{ __('Demo highlights') }}">
                    <template x-for="(item, i) in slides" :key="'dot-' + item.id">
                        <button type="button" @click="go(i)"
                                :aria-label="`{{ __('Show slide') }} ${i + 1} {{ __('of') }} ${slides.length}`"
                                :aria-pressed="i === index"
                                class="flex h-8 w-6 items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#0F172A]">
                            <span class="block h-1.5 w-5 rounded-full transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
                                  :class="i === index ? 'scale-x-100 bg-[#D94B39] dark:bg-[#EF5F4D]' : 'scale-x-75 bg-slate-300 dark:bg-slate-600'"></span>
                        </button>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="step(-1)" aria-label="{{ __('Previous highlight') }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-[#D94B39]/40 hover:text-[#D94B39] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] dark:border-slate-700 dark:text-slate-400 dark:hover:text-[#EF5F4D]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.5 6-6 6 6 6"/></svg>
                    </button>
                    <button type="button" @click="step(1)" aria-label="{{ __('Next highlight') }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-[#D94B39]/40 hover:text-[#D94B39] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] dark:border-slate-700 dark:text-slate-400 dark:hover:text-[#EF5F4D]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9.5 6 6 6-6 6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
