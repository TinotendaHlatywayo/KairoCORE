@extends('help-centre.layout')

@section('title', ($query !== '' ? __('Search').': '.$query : __('Search')).' — '.__('Help').' — '.platform_name())
@section('meta_description', __('Search the '.platform_name().' help centre.'))
@section('seo_robots', 'noindex, follow')

@section('breadcrumbs')
    @if ($query !== '')
        <li aria-hidden="true">/</li>
        <li class="text-slate-900">{{ __('Search') }}</li>
    @endif
@endsection

@section('help-body')
<header class="mb-8">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Search results') }}</h1>
    @if ($query !== '')
        <p class="mt-2 text-slate-600">{{ trans_choice(':count result for ":query"|:count results for ":query"', count($results), ['count' => count($results), 'query' => $query]) }}</p>
    @endif
</header>

@if ($query === '')
    <p class="text-slate-500">{{ __('Type a search term in the box on the left to find a guide.') }}</p>
@elseif (count($results) === 0)
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
        <p class="font-semibold text-slate-900">{{ __('No guides matched your search.') }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ __('Try different words, browse the categories, or contact us directly.') }}</p>
        <a href="{{ route('marketing.contact') }}" class="mt-4 inline-block rounded-xl bg-[#EF5F4D] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#D94B39]">{{ __('Contact us') }}</a>
    </div>
@else
    <ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
        @foreach ($results as $result)
            <li>
                <a href="{{ $result['article']->url() }}" class="block px-5 py-4 transition hover:bg-slate-50">
                    <p class="font-semibold text-slate-900">{{ $result['article']->title }}</p>
                    @if ($result['article']->summary)
                        <p class="mt-1 text-sm text-slate-500">{{ $result['article']->summary }}</p>
                    @endif
                    <p class="mt-1 text-xs font-medium text-slate-400">
                        {{ \App\Support\HelpCentre\HelpCentreRepository::CATEGORY_LABELS[$result['article']->category] ?? $result['article']->category }}
                    </p>
                </a>
            </li>
        @endforeach
    </ul>
@endif
@endsection
