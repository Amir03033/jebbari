@props(['data', 'page', 'number'])

@php($items = collect($data['items'] ?? [])->filter(fn ($i) => loc($i['role'] ?? null)))

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$items->count().')'" />
    <div class="page section-body">
        <dl>
            @foreach ($items as $item)
                <div class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <div class="grid-12 rise gap-y-2 py-6 md:py-8">
                        <dt class="t-label col-span-12 text-muted md:col-span-3">{{ $item['period'] ?? '' }}</dt>
                        <dd class="col-span-12 md:col-span-9">
                            <span class="t-h3 block">{{ loc($item['role']) }}@if (! empty($item['organisation'])), {{ $item['organisation'] }}@endif</span>
                            <span class="t-body mt-2 block text-muted">{{ loc($item['description'] ?? null) }}</span>
                        </dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>
</section>
