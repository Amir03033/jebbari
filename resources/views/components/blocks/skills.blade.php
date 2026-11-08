@props(['data', 'page', 'number'])

@php($items = collect($data['items'] ?? [])->filter(fn ($i) => loc($i['name'] ?? null)))

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$items->count().')'" />
    <div class="page py-16 md:py-28">
        <dl>
            @foreach ($items as $item)
                <div class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <div class="grid-12 rise gap-y-2 py-6 md:py-8">
                        <dt class="t-h3 col-span-12 md:col-span-5">{{ loc($item['name']) }}</dt>
                        <dd class="t-body col-span-12 text-muted md:col-span-6 md:col-start-7">{{ loc($item['description'] ?? null) }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>
</section>
