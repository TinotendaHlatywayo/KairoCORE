@extends('help-centre.layout')

@section('title', $article->title.' — '.__('Help').' — '.platform_name())
@section('meta_description', $article->summary !== '' ? $article->summary : \Illuminate\Support\Str::limit($article->plainText(), 155))

@section('breadcrumbs')
    <li aria-hidden="true">/</li>
    <li><a href="{{ route('help.category', $category) }}" class="hover:text-[#D94B39]">{{ $categoryLabel }}</a></li>
    <li aria-hidden="true">/</li>
    <li class="text-slate-900">{{ $article->title }}</li>
@endsection

@section('help-body')
<div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_224px]">
    <article class="min-w-0">
        <header class="mb-8 border-b border-slate-100 pb-6">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">
                <a href="{{ route('help.category', $category) }}" class="hover:underline">{{ $categoryLabel }}</a>
            </p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $article->title }}</h1>
            @if ($article->summary)
                <p class="mt-3 text-lg text-slate-600">{{ $article->summary }}</p>
            @endif
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium text-slate-400">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    {{ $readMinutes }} {{ __('min read') }}
                </span>
                @if ($article->updatedAt && strtotime($article->updatedAt) !== false)
                    <span>{{ __('Updated') }} {{ date('d M Y', strtotime($article->updatedAt)) }}</span>
                @endif
                <a href="{{ route('help.category', $category) }}" class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-500 transition hover:bg-[#EF5F4D]/10 hover:text-[#D94B39]">{{ $categoryLabel }}</a>
            </div>
        </header>

        <div class="help-article min-w-0">
            {!! $html !!}
        </div>

        @if (count($related) > 0)
            <aside class="mt-12 rounded-2xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-base font-bold text-slate-900">{{ __('Related guides') }}</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($related as $rel)
                        <li>
                            <a href="{{ $rel->url() }}" class="block rounded-xl bg-white p-3 text-sm font-medium text-[#D94B39] shadow-sm transition hover:border-[#EF5F4D]/40 hover:shadow" style="border: 1px solid #e2e8f0;">{{ $rel->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif

        <footer class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
            <p>{{ __('Can\'t find what you need? ') }}<a href="{{ route('marketing.contact') }}" class="font-semibold text-[#D94B39] hover:underline">{{ __('Contact us') }}</a> {{ __('and we\'ll help you out.') }}</p>
        </footer>
    </article>

    @if (count($toc) > 0)
        <aside class="hidden xl:block">
            <div class="sticky top-24">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('On this page') }}</p>
                <nav class="help-toc" aria-label="{{ __('On this page') }}">
                    @foreach ($toc as $entry)
                        <div class="{{ $entry['level'] === 3 ? 'help-toc__sub' : '' }}">
                            <a href="#{{ $entry['id'] }}" data-toc-target="{{ $entry['id'] }}">{{ $entry['text'] }}</a>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>
    @endif
</div>
@endsection

@push('scripts')
@if (count($toc) > 0)
<script>
    (function () {
        var links = Array.prototype.slice.call(document.querySelectorAll('[data-toc-target]'));
        if (!links.length || !('IntersectionObserver' in window)) return;

        var map = {};
        var targets = [];
        links.forEach(function (link) {
            var el = document.getElementById(link.getAttribute('data-toc-target'));
            if (el) { map[el.id] = link; targets.push(el); }
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    links.forEach(function (l) { l.classList.remove('is-active'); });
                    if (map[entry.target.id]) map[entry.target.id].classList.add('is-active');
                }
            });
        }, { rootMargin: '-10% 0px -70% 0px' });

        targets.forEach(function (t) { observer.observe(t); });
    })();
</script>
@endif
@endpush
