@php
    $states = [
        'draft' => __('site.preview.draft'),
        'live' => __('site.preview.live'),
        'live_changes' => __('site.preview.live_changes'),
        'scheduled' => __('site.preview.scheduled'),
    ];
@endphp
<div class="relative z-50 border-b border-line bg-paper-2" role="status">
    <div class="page t-label flex flex-wrap items-center justify-between gap-x-8 gap-y-2 py-3">
        <p><span class="mr-2 inline-block size-1.5 rounded-full bg-signal align-middle" aria-hidden="true"></span>{{ __('site.preview.label') }}</p>
        <p class="text-muted">{{ $states[$preview['state']] ?? '' }}</p>
        <p><span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / <a href="{{ $preview['switchUrl'] }}" class="link link-bare" hreflang="{{ $preview['other'] }}">{{ strtoupper($preview['other']) }}</a></p>
    </div>
</div>
