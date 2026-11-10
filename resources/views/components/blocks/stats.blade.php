@props(['data', 'page', 'number'])

@php
    $cols = [2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3', 4 => 'md:grid-cols-4'];
    $items = collect($data['items'] ?? [])->filter(fn ($i) => is_numeric($i['value'] ?? null) && loc($i['label'] ?? null) !== null)->values();
@endphp

{{-- A few real numbers. They count up once when they scroll into view; the final value is already in the page for everyone else. --}}
<section class="stats" aria-label="{{ loc($data['label'] ?? null) }}">
    <div class="page py-16 md:py-28">
        <p class="t-label mb-10 text-muted md:mb-14" data-reveal="up">{{ loc($data['label'] ?? null) }}</p>
        <ul class="grid grid-cols-2 gap-x-[var(--gutter)] gap-y-12 {{ $cols[min(4, max(2, $items->count()))] }}">
            @foreach ($items as $item)
                @php($value = (int) $item['value'])
                <li data-reveal="group" data-delay="{{ min($loop->index, 4) }}">
                    <p class="stat-figure rise">
                        <span class="count">
                            <span class="count-size" aria-hidden="true">{{ number_format($value, 0, ',', '.') }}</span>
                            <span class="count-live" data-count="{{ $value }}" aria-hidden="true">{{ number_format($value, 0, ',', '.') }}</span>
                        </span>@if (! empty($item['suffix']))<span class="stat-suffix" aria-hidden="true">{{ $item['suffix'] }}</span>@endif
                        <span class="sr-only">{{ $value }}{{ $item['suffix'] ?? '' }}</span>
                    </p>
                    <p class="rise mt-3 max-w-[24ch] text-muted" data-delay="2">{{ loc($item['label']) }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
