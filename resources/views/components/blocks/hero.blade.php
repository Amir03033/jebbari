@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $label = loc($data['label'] ?? null);
    $description = loc($data['description'] ?? null);
    $primary = Site::cta($data, 'cta');
    $secondary = Site::cta($data, 'cta2');
@endphp

{{--
    The opening. The CMS headline is the h1. The wordmark below it is the brand
    moment: set at the width of the screen, its letters rise in turn and the full
    stop lands last. Rings behind echo the full stop. All motion is CSS (motion.css).
--}}
<section class="hero on-ink" aria-labelledby="hero-title" data-scroll="exit">
    <div class="hero-body page-wide" data-reveal="group" data-hero>
        <div class="rings" aria-hidden="true"><span class="ring"></span><span class="ring"></span><span class="ring"></span></div>

        <div class="grid-12 relative items-end gap-y-10">
            <div class="col-span-12 md:col-span-7">
                @if ($label)
                    <p class="t-label rise text-muted" data-delay="1">{{ $label }}</p>
                @endif

                <h1 id="hero-title" class="t-hero mt-6 max-w-[15ch] md:mt-8">
                    <x-lines :lines="$lines" :stop="$data['end_with_stop'] ?? true" />
                </h1>
            </div>

            <div class="col-span-12 flex flex-col items-start gap-8 md:col-span-5">
                @if ($description)
                    <p class="rise max-w-[30ch] text-lg leading-snug text-muted md:text-xl" data-delay="12">{{ $description }}</p>
                @endif
                @if ($primary || $secondary)
                    <div class="rise flex flex-wrap gap-3" data-delay="14">
                        @if ($primary)
                            <x-button :href="$primary['url']">{{ $primary['label'] }}<span class="arrow" aria-hidden="true">→</span></x-button>
                        @endif
                        @if ($secondary)
                            <x-button variant="secondary" :href="$secondary['url']">{{ $secondary['label'] }}</x-button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <p class="wordmark-xl relative -mt-[0.06em]" aria-hidden="true">
            @foreach (['j', 'e', 'b', 'b', 'a', 'r', 'i'] as $letter)<span class="wm-ch"><span>{{ $letter }}</span></span>@endforeach<span class="stop text-signal">.</span>
        </p>
    </div>
</section>
