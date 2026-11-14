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
    'tags' => null,
])

{{--
    A project as a large visual with a quiet caption: index, category, year,
    then the name and one line. With a real image the frame shows it; without
    one it is a typographic tile (x-tile). The whole plate is one link.
--}}
<a href="{{ $href }}" {{ $attributes->class('plate group block') }} data-cursor="{{ $cursor ?? __('site.cta.view_case') }}" data-reveal="group" @if (! $asset && ! $placeholder) data-tile @endif @if ($tags !== null) data-tags="{{ $tags }}" @endif>
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
                        <x-tile :title="$title" :kind="$kind" :large="$large" />
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
