@props(['data', 'page', 'number'])

@php
    use App\Content\Site;
    $mode = $data['mode'] ?? 'featured';
    $projects = Site::projects($mode === 'featured', $mode === 'selected' ? ($data['project_ids'] ?? []) : null)->take(max(1, min(4, (int) ($data['limit'] ?? 4))));
    $workIndex = Site::pageUrl('work');
@endphp

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$projects->count().')'" />
    <div class="page py-20 md:py-36">
        <div class="grid-12 mb-14 gap-y-6 md:mb-24" data-reveal="group">
            <h2 class="t-display col-span-12"><x-lines :lines="[loc($data['label'] ?? null)]" /></h2>
            @if (loc($data['intro'] ?? null))
                <p class="t-statement rise col-span-12 max-w-[30ch] text-muted md:col-span-6 md:col-start-7" data-delay="4">{{ loc($data['intro']) }}</p>
            @endif
        </div>

        <x-project-grid :projects="$projects" />

        @if ($workIndex)
            <p class="mt-20 md:mt-32"><x-button variant="secondary" :href="$workIndex">{{ __('site.cta.all_work') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
        @endif
    </div>
</section>
