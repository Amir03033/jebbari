@props([
    'asset' => null,
    'sizes' => '100vw',
    'eager' => false,
    'alt' => null,
])

{{--
    A media asset as a responsive picture: AVIF and WebP sources where they exist,
    the original as the fallback, explicit width and height (no layout shift),
    lazy loading unless it is above the fold, and the asset's own alt text.
--}}
@php
    $asset = $asset instanceof \App\Models\MediaAsset ? $asset : null;
    $original = $asset?->originalUrl();
    $dims = $asset?->dimensions();
    $sources = $asset ? $asset->sources() : [];
    $text = $alt ?? $asset?->altFor();
    $webp = $sources['webp'] ?? [];
    $largest = $webp ? end($webp) : $original;
    $srcset = fn (array $set) => collect($set)->map(fn ($url, $w) => "{$url} {$w}w")->implode(', ');
@endphp

@if ($original)
    <picture>
        @foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $mime)
            @if (! empty($sources[$format]))
                <source type="{{ $mime }}" srcset="{{ $srcset($sources[$format]) }}" sizes="{{ $sizes }}">
            @endif
        @endforeach
        <img
            src="{{ $largest }}"
            @if ($dims) width="{{ $dims[0] }}" height="{{ $dims[1] }}" @endif
            alt="{{ $text }}"
            @if ($eager) fetchpriority="high" @else loading="lazy" @endif
            decoding="async"
            {{ $attributes->class('h-full w-full object-cover') }}
        >
    </picture>
@endif
