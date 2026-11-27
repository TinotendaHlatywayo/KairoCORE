<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ platform_favicon_url() }}">
    <title>{{ __('Demo session ended') }} — Kairo CORE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-[#f7f5f2] text-[#1d1c1a] antialiased dark:bg-[#141313] dark:text-[#f5f3ef]">
    <main class="mx-auto flex min-h-dvh max-w-xl flex-col items-center justify-center px-6 text-center">
        <p class="mb-4 text-xs font-extrabold uppercase tracking-[0.25em] text-[#D94B39]">{{ __('Kairo CORE Demo') }}</p>
        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">{{ __('Demo session ended') }}</h1>
        <p class="mt-4 max-w-md text-sm leading-7 text-[#615d57] dark:text-[#aaa49b]">
            {{ __('This temporary demo workspace has expired or been removed. Start a new demo to continue exploring Kairo CORE.') }}
        </p>
        <a
            href="{{ \App\Support\DemoSandbox::centralUrl('demo-login') }}"
            class="mt-8 inline-flex items-center justify-center rounded-full bg-[#D94B39] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#c23f2f]"
        >
            {{ __('Start a new demo') }}
        </a>
    </main>
</body>
</html>
