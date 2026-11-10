@props(['data', 'page', 'number'])

@php
    $steps = collect($data['steps'] ?? [])->filter(fn ($s) => loc($s['title'] ?? null))->values();
    $label = loc($data['label'] ?? null);
@endphp

{{--
    A sticky stage: the numeral on the left rolls from 01 to 04 as each step passes,
    a hairline fills beside the steps. The active step comes from a passive scroll
    handler (motion.js); nothing takes over scrolling.
--}}
<section class="process" data-process>
    <x-section-label :number="$number" :label="$label" :meta="'('.$steps->count().')'" />
    <div class="page py-14 md:py-28">
        <div class="grid-12 gap-y-12">
            <div class="col-span-12 md:col-span-5">
                <div class="md:sticky md:top-28" data-reveal="group">
                    <h2 class="t-label mb-8 text-muted">{{ loc($data['heading'] ?? null) ?: $label }}</h2>
                    <div class="process-numerals" aria-hidden="true">
                        @foreach ($steps as $step)<span class="process-num">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>@endforeach
                    </div>
                </div>
            </div>
            <div class="relative col-span-12 pl-8 md:col-span-6 md:col-start-7 md:pl-14">
                <span class="process-track" aria-hidden="true"><span class="process-fill"></span></span>
                @foreach ($steps as $step)
                    <div class="process-step py-10 md:py-20" data-step>
                        <p class="t-label">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="t-h2 mt-4">{{ loc($step['title']) }}</h3>
                        @if (loc($step['description'] ?? null))
                            <p class="t-statement mt-5 max-w-[26ch]">{{ loc($step['description']) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
