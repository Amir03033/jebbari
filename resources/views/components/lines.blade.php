@props([
    'lines' => [],
    'stop' => false,
])

{{-- Server-split lines for the masked reveal. Put data-reveal="group" on the heading that wraps this. --}}
@foreach ($lines as $line)
<span class="line"><span class="line-inner">{{ $line }}@if ($stop && $loop->last)<span class="stop text-signal">.</span>@endif</span></span>
@endforeach
