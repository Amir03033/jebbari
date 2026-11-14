@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $label = loc($data['label'] ?? null);
    $description = loc($data['description'] ?? null);
    $primary = Site::cta($data, 'cta');
    $secondary = Site::cta($data, 'cta2');
    $now = now(config('site.timezone'))->format('H:i');
@endphp

{{--
    The opening. One headline at display size (the h1, from the CMS), revealed
    line by line with the full stop landing last. Behind it a field of light on
    a fine grid: it leans toward the pointer and settles as the page scrolls
    away. All motion is transform and opacity (motion.css, motion.js).
--}}
<section class="hero" aria-labelledby="hero-title" data-scroll="exit">
    <div class="hero-field" data-field aria-hidden="true">
        <span class="hero-grid"></span>
        <span class="hero-glow"></span>
        <span class="hero-glow hero-glow-2"></span>
    </div>

    <div class="hero-body page-wide" data-reveal="group" data-hero>
        @if ($label)
            <p class="hero-label t-label rise" data-delay="1"><span class="hero-label-dot" aria-hidden="true"></span>{{ $label }}</p>
        @endif

        <h1 id="hero-title" class="t-hero hero-title">
            <x-lines :lines="$lines" :stop="$data['end_with_stop'] ?? true" />
        </h1>

        <div class="hero-lower">
            @if ($description)
                <p class="hero-text rise" data-delay="10">{{ $description }}</p>
            @endif
            @if ($primary || $secondary)
                <div class="rise flex flex-wrap gap-3" data-delay="12">
                    @if ($primary)
                        <x-button :href="$primary['url']">{{ $primary['label'] }}<span class="arrow" aria-hidden="true">→</span></x-button>
                    @endif
                    @if ($secondary)
                        <x-button variant="secondary" :href="$secondary['url']">{{ $secondary['label'] }}</x-button>
                    @endif
                </div>
            @endif
        </div>

        <div class="hero-foot rise" data-delay="14">
            <x-status class="text-muted" />
            <p class="t-label hidden text-muted sm:block" x-data="clock('{{ $now }}')">{{ __('site.footer.time') }}: <time class="text-fg" x-text="time">{{ $now }}</time></p>
            <p class="hero-cue t-label text-muted" aria-hidden="true"><span class="hero-cue-line"><span></span></span>{{ __('site.hero.scroll') }}</p>
        </div>
    </div>
</section>
