@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'seo' => null,
    'alternates' => null,
    'preview' => null,
    'overlay' => true,
])

@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    $pageKey = Locale::page();

    // Pages that do not build a full SEO set (style page, errors) get a plain one.
    $meta = $seo ?? [
        'title' => $title ? $title.' · '.config('site.name') : config('site.name').' · '.__('site.descriptor'),
        'description' => $description ?? __('site.meta_description'),
        'canonical' => null,
        'alternates' => $alternates ?? [],
        'xDefault' => null,
        'robots' => $noindex ? 'noindex, nofollow' : null,
        'image' => asset(\App\Support\Seo::DEFAULT_IMAGE),
        'imageAlt' => null,
        'type' => 'website',
        'locale' => $locale === 'nl' ? 'nl_NL' : 'en_GB',
        'localeAlternates' => [],
        'siteName' => config('site.name'),
        'jsonld' => [],
    ];

    if ($preview) {
        $meta['robots'] = 'noindex, nofollow';
    }

    // Where the language switch points: the same page in the other language when it exists.
    $other = Locale::other();
    $switchUrl = $preview['switchUrl'] ?? ($meta['alternates'][$other] ?? ($pageKey ? Locale::url($pageKey, $other) : Locale::url('home', $other)));
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B0B0C" media="(prefers-color-scheme: dark)">
    @if ($meta['robots'])
        <meta name="robots" content="{{ $meta['robots'] }}">
    @endif
    @if (! $meta['robots'] && $meta['canonical'])
        <link rel="canonical" href="{{ $meta['canonical'] }}">
        @foreach ($meta['alternates'] as $alt => $url)
            <link rel="alternate" hreflang="{{ $alt }}" href="{{ $url }}">
        @endforeach
        @if ($meta['xDefault'])
            <link rel="alternate" hreflang="x-default" href="{{ $meta['xDefault'] }}">
        @endif
    @endif
    @if ($meta['canonical'] && ! $meta['robots'])
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:type" content="{{ $meta['type'] }}">
        <meta property="og:url" content="{{ $meta['canonical'] }}">
        <meta property="og:site_name" content="{{ $meta['siteName'] }}">
        <meta property="og:locale" content="{{ $meta['locale'] }}">
        @foreach ($meta['localeAlternates'] as $altLocale)
            <meta property="og:locale:alternate" content="{{ $altLocale }}">
        @endforeach
        <meta property="og:image" content="{{ $meta['image'] }}">
        @if ($meta['imageAlt'])
            <meta property="og:image:alt" content="{{ $meta['imageAlt'] }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $meta['title'] }}">
        <meta name="twitter:description" content="{{ $meta['description'] }}">
        <meta name="twitter:image" content="{{ $meta['image'] }}">
    @endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/fonts/geist-latin-wght-normal.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/fonts/geist-mono-latin-400-normal.woff2') }}">
    <script src="{{ asset('js/motion-flag.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @foreach ($meta['jsonld'] as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-ink focus:px-4 focus:py-2 focus:text-paper rounded-pill text-sm">{{ __('site.skip') }}</a>

    @if ($preview)
        @include('partials.preview-bar')
    @endif

    @include('partials.header')

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
</body>
</html>
