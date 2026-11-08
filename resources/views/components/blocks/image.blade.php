@props(['data', 'page', 'number'])

@php($asset = \App\Models\MediaAsset::with('media')->find($data['media_id']))

@if ($asset)
    <section class="page py-8 md:py-16" data-reveal="group">
        <figure class="rise">
            <div class="plate-frame"><x-picture :asset="$asset" sizes="(min-width: 1360px) 1264px, 100vw" /></div>
            @if (loc($data['caption'] ?? null))
                <figcaption class="t-label mt-3 text-muted">{{ loc($data['caption']) }}</figcaption>
            @endif
        </figure>
    </section>
@endif
