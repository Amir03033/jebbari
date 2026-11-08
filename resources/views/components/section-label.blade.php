@props([
    'number',
    'label',
    'meta' => null,
])

{{-- Every section opens with a full-width hairline and a mono index row. --}}
<div {{ $attributes->class('border-t border-line') }}>
    <div class="page t-label flex items-baseline justify-between gap-6 py-4">
        <p><span class="text-muted">{{ $number }}</span> / {{ $label }}</p>
        @if ($meta)
            <p class="text-muted">{{ $meta }}</p>
        @endif
    </div>
</div>
