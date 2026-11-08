@props([
    'href',
    'arrow' => false,
    'bare' => false,
])

<a href="{{ $href }}" {{ $attributes->class(['link', 'link-bare' => $bare]) }}>{{ $slot }}@if ($arrow)<span class="arrow ml-[0.3em]" aria-hidden="true">→</span>@endif</a>
