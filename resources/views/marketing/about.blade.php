@extends('marketing.layout')

@section('title', platform_name().' - About')
@section('meta_description', __('About KairoCORE School Management System.'))

@section('content')
<section class="bg-white py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">About</h1>
        <p class="mt-6 text-lg text-slate-600 leading-8">KairoCORE is a modern, responsive school management system designed to streamline admissions, academics, finance, HR, communications and daily operations for schools of all sizes.</p>
    </div>
</section>
@push('seo_jsonld')
    <script type="application/ld+json">{!! json_encode(platform_breadcrumb_list([
        ['name' => __('Home'), 'url' => route('marketing.home')],
        ['name' => __('About'), 'url' => route('marketing.about')],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@endsection
