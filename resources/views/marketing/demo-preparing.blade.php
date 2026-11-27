@extends('marketing.layout')

@section('title', platform_name().' Preparing Demo')
@section('meta_description', marketing_text('Your private demo environment is being prepared.'))

@section('content')
<section class="relative overflow-hidden bg-white dark:bg-[#0B1120] transition-colors duration-200">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 right-1/4 h-80 w-80 rounded-full bg-[#D94B39]/8 dark:bg-[#D94B39]/12 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex min-h-[60vh] max-w-2xl items-center px-4 py-16 sm:px-6"
         x-data="{
             attempts: 0,
               busy: false,
               failed: false,
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
                   if (!this.failed) setTimeout(() => this.poll(), 1500);
               }
           }"
         x-init="poll()">
        <div class="w-full rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#D94B39]/10 text-[#D94B39] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                <svg class="h-7 w-7 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path>
                </svg>
            </div>
            <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                {{ marketing_text('Preparing your demo') }}
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                {{ marketing_text('We are setting up an isolated school just for this session. This usually takes a few seconds.') }}
            </p>
            <div class="mx-auto mt-6 h-1.5 w-56 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div class="h-full w-1/3 animate-pulse rounded-full bg-[#D94B39] dark:bg-[#EF5F4D]"></div>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-slate-400" x-text="failed ? @js(__('Something went wrong.')) : @js(__('Please keep this page open'))"></p>
        </div>
    </div>
</section>
@endsection
