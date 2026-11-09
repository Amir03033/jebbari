@props([
    'href',
    'number',
    'title',
    'kind' => null,
    'year' => null,
    'stack' => null,
    'summary' => null,
    'asset' => null,
    'cursor' => null,
    'placeholder' => null,
    'ratio' => 'standard',
    'large' => false,
    'sizes' => '(min-width: 768px) 50vw, 100vw',
    'eager' => false,
])

{{--
    A project presented like a product launch: a large rounded tile, then the name
    at display size, category, year and one line. With a real image the tile shows
    it; without one it is a designed black panel with the name and the ring motif,
    clearly typographic and never a fake interface.
--}}
<a href="{{ $href }}" {{ $attributes->class('plate group block') }} data-cursor="{{ $cursor ?? __('site.cta.view_case') }}" data-reveal="group" @if (! $asset && ! $placeholder) data-tile @endif>
    <div class="plate-frame" data-ratio="{{ $ratio }}">
        <div class="plate-clip">
            <div class="plate-reveal">
                <div class="plate-img">
                    @if ($asset)
                        <x-picture :asset="$asset" :sizes="$sizes" :eager="$eager" />
                    @elseif ($placeholder)
                        <div class="t-label flex h-full items-start justify-between bg-card p-5 text-muted">
                            <span>{{ $placeholder }}</span>
                            <span>{{ $number }}</span>
                        </div>
                    @else
                        <div class="on-ink relative flex h-full flex-col justify-between overflow-clip p-6 md:p-10">
                            <div class="rings tile-rings" aria-hidden="true"><span class="ring"></span><span class="ring"></span><span class="ring"></span><span class="ring-dot"></span></div>
                            <p class="t-label relative text-muted">{{ $kind }}</p>
                            <p class="{{ $large ? 'plate-name-xl' : 'plate-name' }} relative">{{ $title }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="plate-caption">
        <div class="min-w-0">
            <p class="t-label flex flex-wrap gap-x-4 gap-y-1 text-muted">
                <span>{{ $number }}</span>
                @if ($asset || $placeholder)<span>{{ $kind }}</span>@endif
                @if ($year)<span class="tabular-nums">{{ $year }}</span>@endif
                @if ($stack)<span>{{ $stack }}</span>@endif
            </p>
            @if ($asset || $placeholder)
                <h3 class="plate-title {{ $large ? 'plate-name-xl' : 'plate-name' }} mt-3">{{ $title }}</h3>
            @endif
            @if ($summary)
                <p class="mt-3 max-w-[48ch] text-muted">{{ $summary }}</p>
            @endif
        </div>
        <x-arrow-ring class="mt-1" />
    </div>
</a>
