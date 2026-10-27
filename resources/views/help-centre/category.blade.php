@extends('help-centre.layout')

@section('title', $label.' — '.__('Help').' — '.platform_name())
@section('meta_description', $description !== '' ? $description : __('Guides about :category in :platform.', ['category' => $label, 'platform' => platform_name()]))

@section('breadcrumbs')
    <li aria-hidden="true">/</li>
    <li class="text-slate-900">{{ $label }}</li>
@endsection

@section('help-body')
<header class="mb-8 rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-[#EF5F4D]/[0.05] p-6 sm:p-8">
    <div class="flex items-start gap-4">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#EF5F4D]/10 text-[#D94B39]">
            @include('help-centre.partials.category-icon', ['categoryKey' => $category, 'svgClass' => 'h-6 w-6'])
        </span>
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Help Centre') }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">{{ $label }}</h1>
            @if ($description)
                <p class="mt-2 max-w-2xl text-slate-600">{{ $description }}</p>
            @endif
            <p class="mt-3 text-xs font-semibold text-slate-400">{{ count($articles) }} {{ count($articles) === 1 ? __('guide') : __('guides') }}</p>
        </div>
    </div>
</header>

<ul class="grid gap-4 sm:grid-cols-2">
    @foreach ($articles as $article)
        <li>
            <a href="{{ $article->url() }}"
               class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#EF5F4D]/40 hover:shadow-md">
                <h2 class="text-base font-bold text-slate-900 group-hover:text-[#D94B39]">{{ $article->title }}</h2>
                @if ($article->summary)
                    <p class="mt-1.5 flex-1 text-sm text-slate-600">{{ $article->summary }}</p>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-slate-400">
                    <span>{{ $article->readMinutes() }} {{ __('min read') }}</span>
                    @if ($article->updatedAt && strtotime($article->updatedAt) !== false)
                        <span aria-hidden="true">•</span>
                        <span>{{ __('Updated') }} {{ date('d M Y', strtotime($article->updatedAt)) }}</span>
                    @endif
                    <span class="ml-auto text-[#D94B39] opacity-0 transition group-hover:opacity-100">{{ __('Read →') }}</span>
                </div>
            </a>
        </li>
    @endforeach
</ul>
@endsection
