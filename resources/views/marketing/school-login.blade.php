@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - School Login')
@section('meta_description', __('Find your school with its short code and sign in to your school workspace.'))

@push('styles')
<style>
    .sl-input {
        width: 100%;
        border-radius: 1rem;
        border: 1.5px solid #E2E8F0;
        background: #FFFFFF;
        padding: 1rem 1.15rem;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #0F172A;
        outline: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sl-input::placeholder {
        color: #94A3B8;
        letter-spacing: 0.12em;
        font-weight: 600;
    }
    .sl-input:focus {
        border-color: #D94B39;
        box-shadow: 0 0 0 4px rgba(217, 75, 57, 0.14);
    }
    html.dark .sl-input {
        border-color: #334155;
        background: #1E293B;
        color: #F8FAFC;
    }
    html.dark .sl-input:focus {
        border-color: #EF5F4D;
    }
    .sl-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        border: 1px solid #E2E8F0;
        background: #F8FAFC;
        padding: 0.3rem 0.7rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .sl-chip:hover {
        border-color: #D94B39;
        color: #D94B39;
        transform: translateY(-1px);
    }
    html.dark .sl-chip {
        border-color: #334155;
        background: #16213a;
        color: #94A3B8;
    }
</style>
@endpush

@section('content')
<section class="relative overflow-hidden bg-white dark:bg-[#0B1120] transition-colors duration-200">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 left-1/4 h-80 w-80 rounded-full bg-[#D94B39]/8 dark:bg-[#D94B39]/12 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-3xl px-4 pb-20 pt-12 sm:px-6 lg:pt-16 text-center">
        <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39] dark:text-[#EF5F4D]">{{ __('School Login') }}</p>
        <h1 class="reveal reveal-d1 mt-3 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.08]">{{ __('Sign in to your school') }}</h1>
        <p class="reveal reveal-d2 mt-4 text-base sm:text-lg leading-7 text-slate-600 dark:text-slate-300">
            {{ __('Enter your school’s short code and we’ll take you straight to its login page.') }}
        </p>

        <div class="reveal reveal-d3 mx-auto mt-9 max-w-xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] p-6 sm:p-8 text-left shadow-sm transition-colors">
            <form action="{{ route('school.login') }}" method="GET" class="space-y-4">
                <label for="sl-code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    {{ __('School code') }} <span class="text-[#D94B39]">*</span>
                </label>
                <input id="sl-code" name="code" type="text" required maxlength="24"
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       value="{{ $code ?? '' }}"
                       placeholder="{{ __('e.g. TINWAY-PRI') }}"
                       class="sl-input"
                       @error('code') style="border-color:#EF4444" @enderror>

                @if(!empty($error))
                    <p class="flex items-center gap-2 text-sm font-semibold text-red-600 dark:text-red-400">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        {{ $error }}
                    </p>
                @endif

                <button type="submit" class="w-full rounded-xl bg-[#D94B39] hover:bg-[#c14332] px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 transition active:scale-95">
                    {{ __('Go to my school’s login') }}
                </button>
            </form>

            <div class="mt-5 border-t border-slate-100 dark:border-slate-800 pt-4">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Try an example code') }}:</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(['TINWAY-PRI', 'RUJEKO-SEC', 'CHIWARI-PRI'] as $example)
                        <button type="button" class="sl-chip" onclick="var i=document.getElementById('sl-code'); i.value='{{ $example }}'; i.form.submit();">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M3 12h17.25"/></svg>
                            {{ $example }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="reveal reveal-d4 mx-auto mt-8 grid max-w-xl gap-3 sm:grid-cols-2">
            <a href="{{ url('/platform/login') }}" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#0F172A] p-4 text-left transition hover:border-[#D94B39]/40 hover:shadow-sm">
                <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('Platform administrator?') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Use Platform Login instead.') }}</span>
            </a>
            <a href="{{ route('marketing.contact') }}" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#0F172A] p-4 text-left transition hover:border-[#D94B39]/40 hover:shadow-sm">
                <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('Lost your code?') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Ask your school administrator or contact us.') }}</span>
            </a>
        </div>

        <p class="reveal reveal-d4 mt-6 text-xs text-slate-400 dark:text-slate-500">
            {{ __('You can always sign in directly from your school’s own website, too.') }}
        </p>
    </div>
</section>
@endsection
