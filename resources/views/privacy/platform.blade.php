@extends('marketing.layout')

@section('title', __('Privacy Policy').' - '.platform_name())
@section('meta_description', __('Privacy Policy for ').platform_name())

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Privacy Policy') }}</h1>
    <p class="mt-4 text-slate-600 dark:text-slate-300">{{ __('This page explains how ').platform_name().__(' collects, uses and protects your information.') }}</p>
    <h2 class="mt-8 text-xl font-bold text-slate-900 dark:text-white">{{ __('Information we collect') }}</h2>
    <p class="mt-3 text-slate-600 dark:text-slate-300">{{ __('We collect information you provide through forms, account registration and use of the platform.') }}</p>
    <h2 class="mt-8 text-xl font-bold text-slate-900 dark:text-white">{{ __('How we use information') }}</h2>
    <p class="mt-3 text-slate-600 dark:text-slate-300">{{ __('We use information to provide and improve the service, communicate with you and meet legal obligations.') }}</p>
    <h2 class="mt-8 text-xl font-bold text-slate-900 dark:text-white">{{ __('Contact') }}</h2>
    <p class="mt-3 text-slate-600 dark:text-slate-300">{{ __('Questions about this policy can be sent to ').(\Modules\SaaS\Models\PlatformSetting::get('contact','email') ?: 'support@kairocore.co.zw') }}</p>
</article>
@endsection
