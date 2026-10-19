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
<article class="max-w-3xl">
    <header class="mb-8 border-b border-slate-100 pb-6">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">
            <a href="{{ route('help.category', $category) }}" class="hover:underline">{{ $categoryLabel }}</a>
        </p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $article->title }}</h1>
        @if ($article->summary)
            <p class="mt-3 text-lg text-slate-600">{{ $article->summary }}</p>
        @endif
    </header>

    <div class="help-article prose prose-slate max-w-none prose-headings:font-bold prose-headings:text-slate-900 prose-a:text-[#D94B39] prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:before:content-none prose-code:after:content-none prose-strong:text-slate-900">
        {!! $html !!}
    </div>

    @if (count($related) > 0)
        <aside class="mt-12 rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <h2 class="text-base font-bold text-slate-900">{{ __('Related guides') }}</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($related as $rel)
                    <li>
                        <a href="{{ $rel->url() }}" class="text-sm font-medium text-[#D94B39] hover:underline">{{ $rel->title }}</a>
                    </li>
                @endforeach
            </ul>
        </aside>
    @endif

    <footer class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
        <p>{{ __('Can\'t find what you need? ') }}<a href="{{ route('marketing.contact') }}" class="font-semibold text-[#D94B39] hover:underline">{{ __('Contact us') }}</a> {{ __('and we\'ll help you out.') }}</p>
    </footer>
</article>
@endsection
