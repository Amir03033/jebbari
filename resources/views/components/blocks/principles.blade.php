@props(['data', 'page', 'number'])

@php($items = collect($data['items'] ?? [])->filter(fn ($i) => loc($i['name'] ?? null)))

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" />
    <div class="page py-16 md:py-28">
        <ol>
            @foreach ($items as $item)
                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <p class="t-h2 rise grid-12 gap-y-2 py-6 md:py-10">
                        <span class="t-label col-span-2 pt-3 text-muted md:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="col-span-10 md:col-span-9">{{ loc($item['name']) }}</span>
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
