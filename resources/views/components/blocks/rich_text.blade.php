@props(['data', 'page', 'number'])

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null) ?: loc($page->title)" />
    <div class="page py-16 md:py-28" data-reveal="group">
        <div class="grid-12">
            <x-rich-text :html="loc($data['body'])" class="rise col-span-12 md:col-span-8 md:col-start-3" />
        </div>
    </div>
</section>
