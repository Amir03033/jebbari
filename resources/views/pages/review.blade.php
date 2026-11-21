@php
    $sent = session('review_sent');
    $changesSent = $preview->changesSentForThisBuild();
    $expires = $preview->expires_at?->timezone('Europe/Amsterdam')->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('site.review.title', ['name' => $preview->client_name]) }} · {{ config('site.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/motion-flag.js') }}"></script>
    @vite(['resources/css/app.css'])
</head>
<body class="review">
    <header class="review-bar">
        <div class="review-bar-inner">
            <div class="review-meta">
                <p class="t-label"><span class="review-dot" aria-hidden="true"></span>{{ __('site.review.label') }}</p>
                <p class="review-client">{{ $preview->client_name }}@if ($expires)<span class="text-muted"> · {{ __('site.review.valid_until', ['date' => $expires]) }}</span>@endif</p>
                <a href="{{ $url }}" class="link review-open-sm" target="_blank" rel="noopener noreferrer">{{ __('site.review.open_short') }}<span class="sr-only"> {{ __('site.review.new_tab') }}</span><span aria-hidden="true"> ↗</span></a>
            </div>

            <div class="review-actions">
                @if ($preview->approved_at)
                    <p class="review-state" role="status">{{ $sent === 'approved' ? __('site.review.approved_now') : __('site.review.approved_on', ['date' => $preview->approved_at->timezone('Europe/Amsterdam')->locale(app()->getLocale())->isoFormat('D MMMM YYYY, HH:mm')]) }}</p>
                @else
                    @if ($sent === 'changes' || $changesSent)
                        <p class="review-state" role="status">{{ __('site.review.changes_sent') }}</p>
                    @endif
                    <form method="post" action="{{ route('review.approve', $preview->token) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary review-btn">{{ __('site.review.approve') }}</button>
                    </form>
                    <details class="review-changes" @if ($errors->has('change_request')) open @endif>
                        <summary class="btn btn-secondary review-btn">{{ __('site.review.request_changes') }}</summary>
                        <form method="post" action="{{ route('review.changes', $preview->token) }}" class="review-pop">
                            @csrf
                            <x-field name="change_request" type="textarea" :label="__('site.review.changes_label')" :hint="__('site.review.changes_hint')" :required="true" :maxlength="5000" :rows="5" :error="$errors->first('change_request')" :value="old('change_request')" />
                            <button type="submit" class="btn btn-primary review-btn mt-4">{{ __('site.review.send') }}</button>
                        </form>
                    </details>
                @endif
                <a href="{{ $url }}" class="link review-open" target="_blank" rel="noopener noreferrer">{{ __('site.review.open') }}<span class="sr-only"> {{ __('site.review.new_tab') }}</span><span aria-hidden="true"> ↗</span></a>
            </div>
        </div>
    </header>

    <main class="review-stage">
        <iframe src="{{ $url }}" title="{{ __('site.review.frame', ['name' => $preview->client_name]) }}" class="review-frame"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads allow-modals"
                referrerpolicy="no-referrer"></iframe>
    </main>
</body>
</html>
