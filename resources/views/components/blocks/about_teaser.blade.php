@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $asset = filled($data['media_id'] ?? null) ? \App\Models\MediaAsset::with('media')->find($data['media_id']) : null;
    $about = Site::pageUrl('about');
    $paragraphs = collect(preg_split('/\R{2,}/', trim((string) loc($data['body']))))->map(fn ($p) => trim($p))->filter()->values();
    $now = now(config('site.timezone'))->format('H:i');
@endphp

{{-- Personal: the owner's own words at display scale, and a place with its live local time. --}}
<section class="bg-paper-2">
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" />
    <div class="page py-20 md:py-36" data-reveal="group">
        <h2 class="t-display md:max-w-[88%]"><x-lines :lines="[loc($data['heading'])]" /></h2>

        <div class="grid-12 mt-16 items-start gap-y-12 md:mt-28">
            @if ($asset)
                <div class="col-span-12 md:col-span-4">
                    <div class="plate-frame rise" data-ratio="tall" data-delay="3"><x-picture :asset="$asset" sizes="(min-width: 768px) 30vw, 100vw" /></div>
                </div>
            @endif

            <div @class(['col-span-12', 'md:col-span-6 md:col-start-7' => $asset, 'md:col-span-7 md:col-start-6' => ! $asset])>
                <div class="rise space-y-6" data-delay="3">
                    @foreach ($paragraphs as $paragraph)
                        <p @class(['t-statement' => $loop->first, 'text-muted max-w-[56ch]' => ! $loop->first])>{{ $paragraph }}</p>
                    @endforeach
                </div>

                <div class="rise mt-12 flex flex-wrap items-center justify-between gap-6" data-delay="5">
                    <p class="t-label text-muted" x-data="clock('{{ $now }}')">{{ __('site.footer.time') }}: <time x-text="time">{{ $now }}</time></p>
                    @if ($about)
                        <x-button variant="secondary" :href="$about">{{ __('site.cta.about') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
