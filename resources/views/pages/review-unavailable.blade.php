<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('site.review.unavailable_title') }} · {{ config('site.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/motion-flag.js') }}"></script>
    @vite(['resources/css/app.css'])
</head>
<body class="review">
    <main class="review-empty page">
        <p class="t-label text-muted"><span class="review-dot" aria-hidden="true"></span>{{ __('site.review.label') }}</p>
        <h1 class="t-h2 mt-6">{{ __('site.review.unavailable_title') }}</h1>
        <p class="t-body mt-4 text-muted">{{ $expired ? __('site.review.expired_text') : __('site.review.unavailable_text') }}</p>
    </main>
</body>
</html>
