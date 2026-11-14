@props(['items', 'number', 'label'])

{{-- A list read as statements: index, then each point at heading size, hairlines between. --}}
<section>
    <x-section-label :number="$number" :label="$label" :meta="'('.count($items).')'" />
    <div class="page section-body">
        <ul>
            @foreach ($items as $item)
                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <p class="rise grid-12 gap-y-2 py-6 md:py-9">
                        <span class="t-label col-span-2 pt-2 text-muted md:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="t-h3 col-span-10 md:col-span-9 md:text-[clamp(1.5rem,2.6vw,2.25rem)]">{{ $item }}</span>
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
