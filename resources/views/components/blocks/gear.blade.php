@props(['data', 'page', 'number'])

@php($items = collect($data['items'] ?? [])->filter(fn ($i) => loc($i['name'] ?? null)))

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$items->count().')'" />
    <div class="page section-body">
        <x-def-list :items="$items" />
    </div>
</section>
