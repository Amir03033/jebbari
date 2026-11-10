@props(['url', 'title'])

@php
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $links = [
        'LinkedIn' => "https://www.linkedin.com/sharing/share-offsite/?url={$u}",
        'X' => "https://x.com/intent/post?url={$u}&text={$t}",
        'WhatsApp' => 'https://wa.me/?text='.rawurlencode($title.' '.$url),
        __('site.footer.email') => "mailto:?subject={$t}&body={$u}",
    ];
@endphp

{{--
    Share this page. Plain links, so nothing is loaded from another site until
    someone chooses one; on phones the device's own share sheet is offered too.
    The preview card others see comes from the page's title, description and share image.
--}}
<div {{ $attributes->class('share') }}>
    <p class="t-label text-muted">{{ __('site.share.label') }}</p>
    <div class="filter-bar">
        <button type="button" class="filter-chip t-label" data-share="{{ $url }}" data-share-title="{{ $title }}" hidden>{{ __('site.share.native') }}</button>
        <button type="button" class="filter-chip t-label" data-copy="{{ $url }}" data-copied="{{ __('site.contact.copied') }}">{{ __('site.share.copy') }}</button>
        @foreach ($links as $name => $href)
            <a href="{{ $href }}" class="filter-chip t-label" rel="noopener noreferrer" target="_blank">{{ $name }}</a>
        @endforeach
        <span class="t-label self-center text-muted" role="status" aria-live="polite" data-copy-status></span>
    </div>
</div>
