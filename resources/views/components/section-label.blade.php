@props([
    'number',
    'label',
    'meta' => null,
])

{{-- Every section opens with a hairline (drawn on reveal) and a small mono index row. --}}
<div {{ $attributes->class('page') }} data-reveal="group">
    <div class="relative">
        <span class="rule" aria-hidden="true"></span>
        <div class="t-label label-row flex items-baseline justify-between gap-6 py-4">
            <p><span class="text-muted">{{ $number }}</span> / {{ $label }}</p>
            @if ($meta)
                <p class="text-muted">{{ $meta }}</p>
            @endif
        </div>
    </div>
</div>
