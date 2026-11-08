@props([
    'number',
    'label',
    'meta' => null,
])

{{-- Every section opens with a full-width hairline (drawn on reveal) and a mono index row. --}}
<div {{ $attributes->class('relative') }} data-reveal="group">
    <span class="rule" aria-hidden="true"></span>
    <div class="page t-label label-row flex items-baseline justify-between gap-6 py-4">
        <p><span class="text-muted">{{ $number }}</span> / {{ $label }}</p>
        @if ($meta)
            <p class="text-muted">{{ $meta }}</p>
        @endif
    </div>
</div>
