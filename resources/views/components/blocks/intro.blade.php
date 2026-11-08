@props(['data', 'page', 'number'])

{{-- Page opening: label, the page's single h1 and a short text. --}}
<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null) ?: loc($page->title)" />
    <div class="page pb-8 pt-16 md:pb-12 md:pt-28" data-reveal="group">
        <h1 class="t-display md:max-w-[85%]"><x-lines :lines="[loc($data['heading'])]" /></h1>
        @if (loc($data['body'] ?? null))
            <div class="grid-12 mt-10 md:mt-16">
                <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="5">{{ loc($data['body']) }}</p>
            </div>
        @endif
    </div>
</section>
