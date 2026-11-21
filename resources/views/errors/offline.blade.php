@php
    $email = \App\Support\Settings::get('contact.email') ?: config('site.email');
@endphp
{{--
    Shown to visitors while the owner has taken the website offline
    (Admin > Site > Website online). Standalone like the 503 page: no layout
    and no built assets, so it never looks broken.
--}}
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <title>{{ __('site.offline.title') }} · {{ config('site.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/standalone.css') }}">
</head>
<body>
    <main>
        <p class="brand">jebbari<span class="dot"></span></p>
        <div>
            <p class="code">{{ __('site.offline.label') }}</p>
            <h1>{{ __('site.offline.title') }}</h1>
            <p class="text">{{ $message ?? __('site.offline.text') }}</p>
            @if ($back)
                <p class="status"><span class="pulse"></span>{{ __('site.offline.back', ['time' => $back->locale($locale)->translatedFormat(__('site.offline.format'))]) }}</p>
            @endif
            @if ($email)
                <p class="text">{{ __('site.offline.reach') }} <a class="mail" href="mailto:{{ $email }}">{{ $email }}</a></p>
            @endif
        </div>
        <p class="foot">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</p>
    </main>
</body>
</html>
