@props([
    'href',
    'number',
    'title',
    'kind' => null,
    'year' => null,
    'stack' => [],
    'summary' => null,
    'asset' => null,
    'eager' => false,
])

{{--
    A project as a case study card: the facts and the name on one side, a large
    visual on the other. On the homepage these cards stack: each one settles
    under the header and the next slides over it (see .stack in site.css).
--}}
<a href="{{ $href }}" {{ $attributes->class('case plate group') }} data-cursor="{{ __('site.cta.view_case') }}" data-reveal="group" @if (! $asset) data-tile @endif>
    <div class="case-text">
        <p class="t-label rise flex flex-wrap gap-x-4 gap-y-1 text-muted">
            <span>{{ $number }}</span>
            @if ($kind)<span>{{ $kind }}</span>@endif
            @if ($year)<span class="tabular-nums">{{ $year }}</span>@endif
        </p>

        <div>
            <h3 class="case-title plate-title rise" data-delay="1">{{ $title }}</h3>
            @if ($summary)
                <p class="rise mt-4 max-w-[38ch] text-muted" data-delay="2">{{ $summary }}</p>
            @endif
            @if ($stack)
                <ul class="t-label rise mt-6 flex flex-wrap gap-2 text-muted" data-delay="3">
                    @foreach ($stack as $tech)<li class="chip">{{ $tech }}</li>@endforeach
                </ul>
            @endif
        </div>

        <p class="case-more rise" data-delay="4"><x-arrow-ring /><span class="t-label">{{ __('site.cta.view_case') }}</span></p>
    </div>

    <div class="case-visual plate-frame">
        <div class="plate-clip">
            <div class="plate-reveal">
                <div class="plate-img">
                    @if ($asset)
                        <x-picture :asset="$asset" sizes="(min-width: 1024px) 56vw, 100vw" :eager="$eager" />
                    @else
                        <x-tile :title="$title" :large="true" aria-hidden="true" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</a>
