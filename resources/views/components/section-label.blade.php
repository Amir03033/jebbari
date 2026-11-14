@props([
    'number',
    'label',
    'meta' => null,
    'plain' => false,
    'intro' => null,
])

{{--
    How every section opens: a hairline (drawn on reveal), the index number and
    the section title as its one heading. "plain" keeps it to a small label row,
    for sections that bring their own heading.
--}}
<div {{ $attributes->class('page') }} data-reveal="group">
    <div class="relative">
        <span class="rule" aria-hidden="true"></span>
        @if ($plain)
            <div class="t-label label-row flex items-baseline justify-between gap-6 py-4">
                <p><span class="text-muted">{{ $number }}</span> / {{ $label }}</p>
                @if ($meta)
                    <p class="text-muted">{{ $meta }}</p>
                @endif
            </div>
        @else
            <div class="section-head">
                <p class="t-label label-row text-muted">{{ $number }}@if ($meta) <span aria-hidden="true">·</span> {{ $meta }}@endif</p>
                <h2 class="t-h2 section-title"><x-lines :lines="[$label]" /></h2>
                @if ($intro)
                    <p class="section-intro rise text-muted" data-delay="3">{{ $intro }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
