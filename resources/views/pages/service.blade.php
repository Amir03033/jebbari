@php
    use App\Content\PackagePricing;
    use App\Support\Locale;

    $lines = fn (?string $t) => PackagePricing::lines($t);
    $benefits = $lines(loc($service->benefits));
    $included = $lines(loc($service->included));
    $notIncluded = $lines(loc($service->not_included));
    $timeline = loc($service->timeline);
    $description = loc($service->description);
    $contact = \App\Content\Site::pageUrl('contact');
    $n = 1;
    $number = function () use (&$n) {
        return str_pad((string) ++$n, 2, '0', STR_PAD_LEFT);
    };
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-page-opening number="01" :label="__('site.nav.services')" :title="$title" :lead="loc($service->summary)"
            :back="Locale::url('services')" :back-label="__('site.nav.services')" />

        @if ($service->image)
            <div class="page-wide" data-reveal="group">
                <div class="plate-frame" data-ratio="wide"><div class="plate-clip"><div class="plate-reveal"><div class="plate-img"><x-picture :asset="$service->image" sizes="(min-width: 1360px) 1264px, 100vw" :eager="true" /></div></div></div></div>
            </div>
        @endif

        @if ($description || $timeline || $service->tags)
            <section class="page py-16 md:py-28">
                <div class="grid-12 gap-y-12" data-reveal="group">
                    @if ($timeline || $service->tags)
                        <dl class="col-span-12 grid content-start gap-6 md:col-span-4">
                            @if ($timeline)
                                <div class="rise border-t border-line pt-3"><dt class="t-label text-muted">{{ __('site.service.timeline') }}</dt><dd class="mt-1">{{ $timeline }}</dd></div>
                            @endif
                            @if ($service->tags)
                                <div class="rise border-t border-line pt-3"><dt class="t-label text-muted">{{ __('site.service.technology') }}</dt><dd class="mt-3"><ul class="t-label flex flex-wrap gap-2">@foreach ($service->tags as $tag)<li class="chip">{{ $tag }}</li>@endforeach</ul></dd></div>
                            @endif
                        </dl>
                    @endif
                    @if ($description)
                        <x-rich-text :html="$description" class="rise col-span-12 md:col-span-7 md:col-start-6" />
                    @endif
                </div>
            </section>
        @endif

        @foreach ([['benefits', $benefits], ['included', $included], ['not_included', $notIncluded]] as [$key, $items])
            @if ($items)
                <x-statement-list :items="$items" :number="$number()" :label="__('site.service.'.$key)" />
            @endif
        @endforeach

        @if ($packages->isNotEmpty())
            <section class="bg-paper-2">
                <x-section-label :number="$number()" :label="__('site.packages.label')" :meta="'('.$packages->count().')'" />
                <div class="page section-body"><x-package-table :rows="$packages" /></div>
            </section>
        @endif
    </article>

    @if ($others->isNotEmpty())
        <x-service-rows :services="$others" :number="$number()" :label="__('site.service.other')" />
    @endif

    @if ($contact)
        <x-cta-panel :lines="[__('site.service.cta')]">
            <x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button>
        </x-cta-panel>
    @endif
</x-layouts.app>
