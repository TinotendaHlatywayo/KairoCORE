@extends('help-centre.layout')

@section('title', $label.' — '.__('Help').' — '.platform_name())
@section('meta_description', $description !== '' ? $description : __('Guides about '.$label.' in '.platform_name().'.'))

@section('breadcrumbs')
    <li aria-hidden="true">/</li>
    <li class="text-slate-900">{{ $label }}</li>
@endsection

@section('help-body')
<header class="mb-8">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ $label }}</p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $label }}</h1>
    @if ($description)
        <p class="mt-3 max-w-2xl text-slate-600">{{ $description }}</p>
    @endif
</header>

<ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
    @foreach ($articles as $article)
        <li>
            <a href="{{ $article->url() }}" class="block px-5 py-4 transition hover:bg-slate-50">
                <p class="font-semibold text-slate-900">{{ $article->title }}</p>
                @if ($article->summary)
                    <p class="mt-1 text-sm text-slate-500">{{ $article->summary }}</p>
                @endif
            </a>
        </li>
    @endforeach
</ul>
@endsection
