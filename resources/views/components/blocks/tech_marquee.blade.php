@props(['data', 'page', 'number'])

@php
    $stack = \App\Content\Site::stack();
@endphp

{{-- The technologies used across the published projects, drifting slowly. Real data, nothing invented. --}}
<section class="marquee" aria-label="{{ __('site.marquee.label') }}">
    <ul class="marquee-list">
        @foreach ($stack as $tech)
            <li>{{ $tech }}</li>
        @endforeach
    </ul>
    <div class="marquee-track" aria-hidden="true">
        @foreach ([1, 2] as $copy)
            <ul class="marquee-group">
                @foreach ($stack as $tech)
                    <li>{{ $tech }}<span class="marquee-dot"></span></li>
                @endforeach
            </ul>
        @endforeach
    </div>
</section>
