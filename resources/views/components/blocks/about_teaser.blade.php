@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $asset = filled($data['media_id'] ?? null) ? \App\Models\MediaAsset::with('media')->find($data['media_id']) : null;
    $about = Site::pageUrl('about');
@endphp

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" />
    <div class="page py-16 md:py-28" data-reveal="group">
        <div class="grid-12 gap-y-10">
            <h2 class="t-h2 col-span-12 md:col-span-6"><x-lines :lines="[loc($data['heading'])]" /></h2>
            <div class="col-span-12 md:col-span-5 md:col-start-8">
                <div class="t-body rise space-y-4 text-muted" data-delay="3">
                    @foreach (preg_split('/\R{2,}/', trim((string) loc($data['body']))) as $paragraph)
                        <p>{{ trim($paragraph) }}</p>
                    @endforeach
                </div>
                @if ($about)
                    <p class="rise mt-8" data-delay="4"><x-link :href="$about" arrow>{{ __('site.cta.about') }}</x-link></p>
                @endif
            </div>
            @if ($asset)
                <div class="col-span-12 md:col-span-6 md:col-start-1">
                    <div class="plate-frame"><x-picture :asset="$asset" sizes="(min-width: 768px) 50vw, 100vw" /></div>
                </div>
            @endif
        </div>
    </div>
</section>
