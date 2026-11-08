@props([
    'variant' => 'primary',
    'href' => null,
])

@php($class = 'btn btn-'.$variant)

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($class) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($class) }}>{{ $slot }}</button>
@endif
