@props([
    'href',
    'number',
    'title',
    'kind' => null,
    'year' => null,
    'stack' => null,
    'cursor' => 'View case',
    'placeholder' => null,
])

{{-- A case-study plate: framed image, mono caption row. No browser chrome. --}}
<a href="{{ $href }}" {{ $attributes->class('plate block') }} data-cursor="{{ $cursor }}" data-reveal="group">
    <div class="plate-frame">
        <div class="plate-clip">
            <div class="plate-reveal">
                <div class="plate-img">
                    @if ($slot->isNotEmpty())
                        {{ $slot }}
                    @else
                        <div class="t-label flex h-full items-start justify-between bg-paper-2 p-4 text-muted">
                            <span>{{ $placeholder ?? '[[ OWNER: screenshot ]]' }}</span>
                            <span>{{ $number }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="plate-caption t-label grid-12 gap-y-1">
        <span class="col-span-2 text-muted md:col-span-1">{{ $number }}</span>
        <span class="plate-title col-span-10 md:col-span-3">{{ $title }}</span>
        <span class="col-span-12 text-muted md:col-span-3">{{ $kind }}</span>
        <span class="col-span-4 text-muted md:col-span-1">{{ $year }}</span>
        <span class="col-span-8 text-muted md:col-span-4 md:text-right">{{ $stack }}<span class="plate-arrow" aria-hidden="true"> →</span></span>
    </div>
</a>
