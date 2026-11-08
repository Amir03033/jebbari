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
    'sizes' => '(min-width: 768px) 50vw, 100vw',
    'eager' => false,
])

{{-- A case-study plate: framed image, mono caption row, short description. No browser chrome. --}}
<a href="{{ $href }}" {{ $attributes->class('plate group block') }} data-cursor="{{ $cursor ?? __('site.cta.view_case') }}" data-reveal="group" @if (! $asset && ! $placeholder) data-tile @endif>
    <div class="plate-frame">
        <div class="plate-clip">
            <div class="plate-reveal">
                <div class="plate-img">
                    @if ($asset)
                        <x-picture :asset="$asset" :sizes="$sizes" :eager="$eager" />
                    @elseif ($placeholder)
                        <div class="t-label flex h-full items-start justify-between bg-paper-2 p-4 text-muted">
                            <span>{{ $placeholder }}</span>
                            <span>{{ $number }}</span>
                        </div>
                    @else
                        {{-- No image yet: a typographic tile built from the project's real title and category. --}}
                        <div class="flex h-full flex-col justify-between bg-paper-2 p-6 md:p-8">
                            <p class="t-label text-muted">{{ $kind }}</p>
                            <p class="t-h2 max-w-[14ch]">{{ $title }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="plate-caption t-label grid-12 gap-y-1">
        <span class="col-span-2 text-muted md:col-span-1">{{ $number }}</span>
        <span class="plate-title col-span-10 md:col-span-4">{{ $title }}</span>
        <span class="col-span-12 text-muted md:col-span-3">{{ $kind }}</span>
        <span class="col-span-4 text-muted md:col-span-1">{{ $year }}</span>
        <span class="col-span-8 text-muted md:col-span-3 md:text-right">{{ $stack }}<span class="plate-arrow" aria-hidden="true"> →</span></span>
    </div>
    @if ($summary)
        <p class="mt-3 max-w-[56ch] text-muted">{{ $summary }}</p>
    @endif
</a>
