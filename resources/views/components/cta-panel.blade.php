@props([
    'lines' => [],
    'number' => '→',
    'label' => null,
    'id' => null,
])

{{--
    How a page ends: a contrast panel set into the page with one question at
    display size and the next step beneath it. A soft light rises inside it as
    it scrolls into view.
--}}
<section class="cta-wrap" @if ($id) aria-labelledby="{{ $id }}" @endif>
    <div class="cta panel on-ink" data-scroll="enter">
        <span class="cta-grid" aria-hidden="true"></span>
        <span class="cta-glow" aria-hidden="true"></span>
        <div class="page-wide relative" data-reveal="group">
            <p class="t-label label-row"><span class="text-muted">{{ $number }}</span> / {{ $label ?? __('site.nav.contact') }}</p>
            <h2 @if ($id) id="{{ $id }}" @endif class="t-display mt-8 md:mt-12 md:max-w-[85%]"><x-lines :lines="$lines" /></h2>
            <div class="rise mt-10 md:mt-16" data-delay="5">{{ $slot }}</div>
        </div>
    </div>
</section>
