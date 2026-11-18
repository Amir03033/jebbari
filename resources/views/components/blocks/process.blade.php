@props(['data', 'page', 'number'])

@php
    $steps = collect($data['steps'] ?? [])->filter(fn ($s) => loc($s['title'] ?? null))->values();
    $label = loc($data['label'] ?? null);
    $pad = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
@endphp

{{--
    How I work. On a laptop the left column stays in view: a numeral that rolls
    from 01 to 04 and an index of the steps with a line that fills, so you
    always know where you are. The steps are cards on the right that settle
    under the header one after another. On phones, and with reduced motion,
    the cards are a plain list. The active step comes from a passive scroll
    handler (motion.js); nothing takes over scrolling.
--}}
<section class="process" data-process>
    <x-section-label :number="$number" :label="loc($data['heading'] ?? null) ?: $label" :meta="'('.$steps->count().')'" />
    <div class="page section-body">
        <div class="grid-12 gap-y-8">
            {{-- A visual summary of the steps that follow; the cards carry the same words. --}}
            <div class="hidden md:col-span-5 md:block" aria-hidden="true">
                <div class="process-aside" data-reveal="group">
                    <div class="process-numerals rise">
                        @foreach ($steps as $step)<span class="process-num">{{ $pad($loop->iteration) }}</span>@endforeach
                    </div>
                    <div class="process-index rise" data-delay="1">
                        <span class="process-track"><span class="process-fill"></span></span>
                        <ol>
                            @foreach ($steps as $step)
                                <li class="process-index-item"><span class="t-label">{{ $pad($loop->iteration) }}</span>{{ loc($step['title']) }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>

            <ol class="process-cards col-span-12 md:col-span-7">
                @foreach ($steps as $step)
                    <li class="process-card-wrap" data-step>
                        <div class="process-card" data-reveal="group">
                            <p class="t-label rise text-muted">{{ $pad($loop->iteration) }} / {{ $pad($steps->count()) }}</p>
                            <h3 class="t-h2 rise mt-6" data-delay="1">{{ loc($step['title']) }}</h3>
                            @if (loc($step['description'] ?? null))
                                <p class="t-statement rise mt-4 max-w-[30ch] text-muted" data-delay="2">{{ loc($step['description']) }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
