@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - School Login')
@section('meta_description', __('Find your school and sign in to your school workspace.'))

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
        letter-spacing: 0.06em;
        color: #0F172A;
        outline: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    /* Room for the search icon that sits inside the field. */
    .sl-input--search {
        padding-left: 3.25rem;
    }
    .sl-input::placeholder {
        color: #94A3B8;
        letter-spacing: 0.06em;
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
    .sl-result {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        width: 100%;
        border-radius: 0.9rem;
        border: 1px solid #E2E8F0;
        background: #FFFFFF;
        padding: 0.8rem 0.95rem;
        text-align: left;
        transition: all 0.15s ease;
    }
    .sl-result:hover {
        border-color: #D94B39;
        background: #FEF7F6;
        transform: translateY(-1px);
    }
    html.dark .sl-result {
        border-color: #334155;
        background: #0F172A;
    }
    html.dark .sl-result:hover {
        border-color: #EF5F4D;
        background: #16213a;
    }
    .sl-result-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        color: #D94B39;
        background: #FEF2F0;
        border-radius: 0.45rem;
        padding: 0.15rem 0.4rem;
        white-space: nowrap;
    }
    html.dark .sl-result-code {
        color: #EF5F4D;
        background: rgba(239, 95, 77, 0.14);
    }
    .sl-empty {
        border-radius: 0.9rem;
        border: 1px dashed #CBD5E1;
        padding: 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748B;
    }
    html.dark .sl-empty {
        border-color: #334155;
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
            {{ __('Search for your school and we’ll take you straight to its login page.') }}
        </p>

        <div class="reveal reveal-d3 mx-auto mt-9 max-w-xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] p-6 sm:p-8 text-left shadow-sm transition-colors"
             x-data="schoolLoginSearch(@js(route('school.login.suggest')))">
            <form action="{{ route('school.login') }}" method="GET" class="space-y-4"
                  @submit="if (exactUrl) { window.location.href = exactUrl; return false; }">
                <label for="sl-code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    {{ __('Your school') }} <span class="text-[#D94B39]">*</span>
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    </span>
                    <input id="sl-code" name="q" type="search" required maxlength="60"
                           autocomplete="off" spellcheck="false"
                           value="{{ $term ?? '' }}"
                           placeholder="{{ __('e.g. Chiwarira Primary, or TINWAY-PRI') }}"
                           class="sl-input sl-input--search"
                           x-model="query"
                           @input.debounce.250ms="search()"
                           @focus="query.length >= 2 && search()"
                           aria-describedby="sl-help"
                           aria-autocomplete="list"
                           aria-controls="sl-results"
                           @error('q') style="border-color:#EF4444" @enderror>
                </div>

                <p id="sl-help" class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    {{ __('Type your school’s short code or name.') }}
                </p>

                @if(!empty($error))
                    <p class="flex items-center gap-2 text-sm font-semibold text-red-600 dark:text-red-400">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        {{ $error }}
                    </p>
                @endif

                <div id="sl-results" class="space-y-2" role="listbox" aria-label="{{ __('Matching schools') }}">
                    {{--
                        The answer to a submitted search, and the whole list when
                        JavaScript is unavailable. The live list below takes
                        over as soon as the visitor starts typing.
                    --}}
                    <div x-show="! searched" class="space-y-2">
                        @forelse($results as $school)
                            <a href="{{ route('school.go', ['code' => $school->login_code]) }}" class="sl-result" role="option">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ $school->name }}</span>
                                    @if($school->region)
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($school->region) }}</span>
                                    @endif
                                </span>
                                <span class="sl-result-code">{{ $school->login_code }}</span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M3 12h17.25"/></svg>
                            </a>
                        @empty
                            @if(!empty($term))
                                <p class="sl-empty">{{ __('No school matches that yet. Check the spelling, or try the short code.') }}</p>
                            @endif
                        @endforelse
                    </div>

                    {{-- The live list, filled in as the visitor types. --}}
                    <div x-show="searched" class="space-y-2">
                        <template x-for="school in results" :key="school.url">
                            <a :href="school.url" class="sl-result" role="option">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-slate-900 dark:text-white" x-text="school.name"></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="school.region || 'School workspace'"></span>
                                </span>
                                <span class="sl-result-code" x-text="school.code"></span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M3 12h17.25"/></svg>
                            </a>
                        </template>

                        <template x-if="results.length === 0">
                            <p class="sl-empty">{{ __('No school matches that yet. Check the spelling, or try the short code.') }}</p>
                        </template>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-[#D94B39] hover:bg-[#c14332] px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 transition active:scale-95">
                    {{ __('Go to my school’s login') }}
                </button>
            </form>

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

@push('scripts')
<script>
    // Live school search: type a code or any ordered run of a school's
    // letters and the matching schools are offered to click. Without
    // JavaScript the form still works — it submits and the page renders the
    // same list.
    window.schoolLoginSearch = function (endpoint) {
        return {
            query: @js($term ?? ''),
            results: [],
            searched: false,
            exactUrl: null,
            controller: null,

            search() {
                const term = this.query.trim();

                if (term.length < 2) {
                    this.results = [];
                    this.searched = false;
                    this.exactUrl = null;

                    return;
                }

                if (this.controller) {
                    this.controller.abort();
                }

                this.controller = new AbortController();

                fetch(endpoint + '?q=' + encodeURIComponent(term), {
                    signal: this.controller.signal,
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        this.results = data.results || [];
                        this.exactUrl = data.exact || null;
                        this.searched = true;
                    })
                    .catch(() => {});
            },
        };
    };
</script>
@endpush
