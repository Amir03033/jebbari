@props(['page', 'title' => null, 'label' => null, 'lead' => null])

{{-- Fallback opening for pages without an intro block: the page title as the single h1. --}}
<section>
    <x-section-label number="01" :label="$label ?? loc($page->title)" />
    <div class="page pb-8 pt-16 md:pb-12 md:pt-28" data-reveal="group">
        <h1 class="t-display md:max-w-[85%]"><x-lines :lines="[$title ?? loc($page->title)]" /></h1>
        @if ($lead)
            <div class="grid-12 mt-10 md:mt-16">
                <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="5">{{ $lead }}</p>
            </div>
        @endif
    </div>
</section>
