@props([
    'href',
    'arrow' => false,
    'bare' => false,
])

<a href="{{ $href }}" {{ $attributes->class(['link', 'link-bare' => $bare]) }}>{{ $slot }}@if ($arrow)<span class="arrow" aria-hidden="true"> →</span>@endif</a>
