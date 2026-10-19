@extends('help-centre.layout')

@section('title', __('Help Centre').' — '.platform_name())
@section('meta_description', __('Step-by-step guides for '.platform_name().': getting started, academics, students, admissions, fees, report cards, permissions and troubleshooting.'))

@section('help-body')
<header class="mb-10">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Help Centre') }}</p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('How can we help?') }}</h1>
    <p class="mt-3 max-w-2xl text-slate-600">{{ __('Verified, step-by-step guides for every part of '.platform_name().' — written from the product itself, so every button and menu we mention really exists.') }}</p>
</header>

<div class="mb-12 grid gap-4 sm:grid-cols-2">
    @foreach ($categories as $cat)
        <a href="{{ route('help.category', $cat['key']) }}"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#EF5F4D]/40 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900 group-hover:text-[#D94B39]">{{ $cat['label'] }}</h2>
                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $cat['count'] }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600">{{ $cat['description'] }}</p>
        </a>
    @endforeach
</div>

<h2 class="mb-4 text-lg font-bold text-slate-900">{{ __('All guides') }}</h2>
<ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
    @foreach ($articles as $article)
        <li>
            <a href="{{ $article->url() }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ $article->title }}</p>
                    @if ($article->summary)
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $article->summary }}</p>
                    @endif
                </div>
                <span class="shrink-0 text-xs font-medium text-slate-400">
                    {{ \App\Support\HelpCentre\HelpCentreRepository::CATEGORY_LABELS[$article->category] ?? $article->category }}
                </span>
            </a>
        </li>
    @endforeach
</ul>
@endsection
