@props([
    'name',
    'value',
    'type' => 'radio',
    'checked' => false,
    'title',
    'text' => null,
    'meta' => null,
])

{{-- One option as a quiet card: the real input stays in the card (keyboard and screen readers), the card shows the state. --}}
<label {{ $attributes->only('class')->class('choice') }}>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" class="choice-input" @checked($checked) {{ $attributes->except('class') }}>
    <span class="choice-body">
        <span class="choice-mark" aria-hidden="true"></span>
        <span class="choice-title">{{ $title }}</span>
        @if ($meta)
            <span class="choice-meta t-label text-muted">{{ $meta }}</span>
        @endif
        @if ($text)
            <span class="choice-text text-sm text-muted">{{ $text }}</span>
        @endif
    </span>
</label>
