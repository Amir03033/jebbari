@php
    $segment = request()->segment(1);
    $locale = in_array($segment, config('site.locales'), true) ? $segment : config('site.default_locale');
    app()->setLocale($locale);
@endphp
{{--
    Stands on its own on purpose: no layout, no database and no built assets, so
    it still shows while the site is being updated or when something is broken.
--}}
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('site.error.500_title') }} · {{ config('site.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/standalone.css') }}">
</head>
<body>
    <main>
        <p class="brand">jebbari<span class="dot"></span></p>
        <div>
            <p class="code">500</p>
            <h1>{{ __('site.error.500_title') }}</h1>
            <p class="text">{{ __('site.error.500_text') }}</p>
            <p><a class="button" href="{{ url('/'.$locale) }}">{{ __('site.error.home') }}</a></p>
        </div>
        <p class="foot">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</p>
    </main>
</body>
</html>
