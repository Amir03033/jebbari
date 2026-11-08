@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $label = loc($data['label'] ?? null);
    $description = loc($data['description'] ?? null);
    $primary = Site::cta($data, 'cta');
    $secondary = Site::cta($data, 'cta2');
@endphp

{{-- Dark opening. The headline reveals line by line and the full stop lands last. --}}
<section class="on-ink relative overflow-clip" aria-labelledby="hero-title">
    <div class="hero-grid page hidden md:grid grid-12" aria-hidden="true"><span></span></div>

    <div class="page relative pb-12 pt-16 md:pb-20 md:pt-28" data-reveal="group" data-hero>
        @if ($label)
            <p class="t-label rise text-muted" data-delay="1">{{ $label }}</p>
        @endif

        <h1 id="hero-title" class="t-display mt-8 md:mt-10 md:max-w-[88%]">
            <x-lines :lines="$lines" :stop="$data['end_with_stop'] ?? true" />
        </h1>

        <div class="grid-12 mt-12 gap-y-8 md:mt-24">
            @if ($description)
                <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="12">{{ $description }}</p>
            @endif
            @if ($primary || $secondary)
                <div class="rise col-span-12 flex flex-wrap gap-4 md:col-span-5 md:col-start-8" data-delay="14">
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

    {{-- Brand sign-off: the wordmark at display size, with the descriptor. --}}
    <div class="relative border-t border-line" data-reveal="group">
        <div class="page flex items-end justify-between gap-6 py-6 md:py-8">
            <p class="rise text-[clamp(2.5rem,7vw,6rem)] font-medium leading-none tracking-[-0.04em]" data-delay="16"><x-wordmark /></p>
            <p class="t-label rise pb-1 text-right text-muted" data-delay="16">{{ __('site.descriptor') }}<br>Zwolle &amp; Amsterdam</p>
        </div>
    </div>
</section>
