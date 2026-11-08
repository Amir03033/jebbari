@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
])

@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    $page = Locale::page();
    $fullTitle = $title ? $title.' · '.config('site.name') : config('site.name').' · '.__('site.tagline');
    $description ??= __('site.meta_description');
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#F5F3EE">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @elseif ($page)
        <link rel="canonical" href="{{ Locale::url($page) }}">
        @foreach (Locale::all() as $alt)
            <link rel="alternate" hreflang="{{ $alt }}" href="{{ Locale::url($page, $alt) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ Locale::url($page, config('site.default_locale')) }}">
    @endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/fonts/geist-mono-latin-400-normal.woff2') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-ink focus:px-4 focus:py-2 focus:text-paper rounded-ui">{{ __('site.skip') }}</a>

    @include('partials.header')

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
</body>
</html>
