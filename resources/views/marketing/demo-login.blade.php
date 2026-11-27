@extends('marketing.layout')

@section('favicon', asset('images/kairocore-icon.png'))
@section('title', platform_name().' Live Demo')
@section('meta_description', marketing_text('Sign in to the shared demo account and explore every module and role without registering your school.'))

@push('styles')
<style>
    .dm-input {
        width: 100%;
        border-radius: 1rem;
        border: 1.5px solid #E2E8F0;
        background: #FFFFFF;
        padding: 1rem 1.15rem;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0F172A;
        outline: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .dm-input::placeholder {
        color: #94A3B8;
        letter-spacing: 0.02em;
        font-weight: 600;
    }
    .dm-input:focus {
        border-color: #D94B39;
        box-shadow: 0 0 0 4px rgba(217, 75, 57, 0.14);
    }
    html.dark .dm-input {
        border-color: #334155;
        background: #1E293B;
        color: #F8FAFC;
    }
    html.dark .dm-input:focus {
        border-color: #EF5F4D;
    }
</style>
@endpush

@section('content')
<section class="relative overflow-hidden bg-white dark:bg-[#0B1120] transition-colors duration-200">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 right-1/4 h-80 w-80 rounded-full bg-[#D94B39]/8 dark:bg-[#D94B39]/12 blur-3xl"></div>
    </div>

    <div class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-20 pt-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:pt-16">
        <div>
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39] dark:text-[#EF5F4D]">{{ marketing_text('Live demo') }}</p>
            <h1 class="reveal reveal-d1 mt-3 text-4xl font-extrabold leading-[1.08] tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                {{ marketing_text('Try every part of Kairo CORE.') }}
            </h1>
            <p class="reveal reveal-d2 mt-4 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg">
                {{ marketing_text('Sign in with the shared demo account to explore a fully populated school. No registration, no setup, no waiting for approval.') }}
            </p>

            <div class="reveal reveal-d3 mt-8 rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#0F172A] p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    {{ marketing_text('Switch between every role from the topbar') }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (App\Security\DemoPersona::personas() as $role)
                        <span class="rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#111C31] px-3 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ $role['label'] }}
                        </span>
                    @endforeach
                </div>
                <p class="mt-4 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    {{ marketing_text('Each role opens with only its own permissions, and the student role opens the student portal.') }}
                </p>
            </div>

            <div class="reveal reveal-d4 mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#D94B39]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    {{ marketing_text('Real sample data') }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#D94B39]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    {{ marketing_text('All modules enabled') }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#D94B39]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    {{ marketing_text('No school registration') }}
                </span>
            </div>
        </div>

        <div class="reveal reveal-d2 mx-auto w-full max-w-xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] p-6 shadow-sm transition-colors sm:p-8">
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ marketing_text('Sign in to the demo') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                {{ marketing_text('Use the demo email and password shared with you.') }}
            </p>

            <form method="POST" action="{{ route('demo.login.submit') }}" class="mt-6 space-y-4">
                @csrf

                @if ($errors->any())
                    <p class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" role="alert">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        {{ $errors->first() }}
                    </p>
                @endif

                <div>
                    <label for="demo-email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ marketing_text('Demo email') }} <span class="text-[#D94B39]">*</span>
                    </label>
                    <input id="demo-email" name="email" type="email" required autocomplete="username" spellcheck="false"
                           placeholder="demo@example.com" value="{{ old('email') }}" class="dm-input mt-2">
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="demo-password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ marketing_text('Password') }} <span class="text-[#D94B39]">*</span>
                    </label>
                    <div class="relative mt-2">
                        <input id="demo-password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password"
                               placeholder="••••••••" class="dm-input pr-12">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-2xl text-slate-400 transition hover:text-[#D94B39] dark:hover:text-[#EF5F4D]"
                                :aria-label="showPassword ? @js(__('Hide password')) : @js(__('Show password'))"
                                :title="showPassword ? @js(__('Hide password')) : @js(__('Show password'))">
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 1 0 0 1 0-.644 10.75 10.75 0 0 1 19.928 0 1 1 0 0 1 0 .644 10.75 10.75 0 0 1-19.928 0Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 12.322a1 1 0 0 0 0 .644 10.75 10.75 0 0 0 19.428 3.81M6.228 6.228A10.75 10.75 0 0 1 21.964 11.678a1 1 0 0 1 0 .644 10.8 10.8 0 0 1-.934 1.956M3 3l18 18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.598 10.598a3 3 0 1 0 4.244 4.244"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#D94B39] px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 transition hover:bg-[#c14332] active:scale-95">
                    {{ marketing_text('Enter the demo dashboard') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M3 12h17.25"/></svg>
                </button>
            </form>

            <div class="mt-6 grid gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:grid-cols-2">
                <a href="{{ route('marketing.home') }}" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#111C31] p-4 text-left transition hover:border-[#D94B39]/40 hover:shadow-sm">
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ marketing_text('Back to home') }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ marketing_text('Learn more about the platform.') }}</span>
                </a>
                <a href="{{ route('school.login') }}" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#111C31] p-4 text-left transition hover:border-[#D94B39]/40 hover:shadow-sm">
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ marketing_text('School Login') }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ marketing_text('Sign in to your own school instead.') }}</span>
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
