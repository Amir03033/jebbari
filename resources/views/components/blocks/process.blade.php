@props(['data', 'page', 'number'])

@php
    $steps = collect($data['steps'] ?? [])->filter(fn ($s) => loc($s['title'] ?? null))->values();
    $label = loc($data['label'] ?? null);
@endphp

{{-- A hairline fills as you scroll and each step lights as it passes (see motion.css). --}}
<section>
    <x-section-label :number="$number" :label="$label" :meta="'('.$steps->count().')'" />
    <div class="page py-16 md:py-28">
        <div class="grid-12 gap-y-10">
            <div class="col-span-12 md:col-span-5">
                <div class="md:sticky md:top-28" data-reveal="group">
                    <h2 class="t-h2 max-w-[12ch]"><x-lines :lines="[loc($data['heading'] ?? null) ?: $label]" /></h2>
                </div>
            </div>
            <div class="relative col-span-12 pl-8 md:col-span-6 md:col-start-7 md:pl-12" data-process>
                <span class="process-track" aria-hidden="true"><span class="process-fill"></span></span>
                @foreach ($steps as $step)
                    <div class="process-step py-12 md:py-20" data-step>
                        <p class="t-label">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="t-h3 mt-2">{{ loc($step['title']) }}</h3>
                        @if (loc($step['description'] ?? null))
                            <p class="t-body mt-3">{{ loc($step['description']) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
