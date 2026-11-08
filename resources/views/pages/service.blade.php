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
    $locale = app()->getLocale();
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-section-label number="01" :label="__('site.nav.services')" />

        <header class="page pb-10 pt-12 md:pb-16 md:pt-20" data-reveal="group">
            <nav aria-label="{{ __('site.breadcrumb') }}" class="t-label rise mb-8 text-muted">
                <a href="{{ Locale::url('services') }}" class="link link-bare">← {{ __('site.nav.services') }}</a>
            </nav>
            <h1 class="t-display md:max-w-[90%]"><x-lines :lines="[$title]" /></h1>
            <div class="grid-12 mt-10 md:mt-16">
                <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="5">{{ loc($service->summary) }}</p>
            </div>
        </header>

        @if ($service->image)
            <div class="page pb-16 md:pb-28" data-reveal="group">
                <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$service->image" sizes="(min-width: 1360px) 1264px, 100vw" :eager="true" /></div></div></div>
            </div>
        @endif

        @if ($description)
            <div class="page pb-16 md:pb-28"><div class="grid-12"><x-rich-text :html="$description" class="col-span-12 md:col-span-8 md:col-start-5" /></div></div>
        @endif

        @foreach ([['benefits', $benefits], ['included', $included], ['not_included', $notIncluded]] as [$key, $items])
            @if ($items)
                <section>
                    <x-section-label :number="'0'.($loop->index + 2)" :label="__('site.service.'.$key)" :meta="'('.count($items).')'" />
                    <div class="page py-12 md:py-20">
                        <ul>
                            @foreach ($items as $item)
                                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                                    <span class="rule" aria-hidden="true"></span>
                                    <p class="rise grid-12 gap-y-1 py-5"><span class="t-label col-span-2 pt-1 text-muted md:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="t-h3 col-span-10 md:col-span-8">{{ $item }}</span></p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif
        @endforeach

        @if ($timeline || $service->tags)
            <div class="page py-8 md:py-12">
                <dl class="grid-12 gap-y-6">
                    @if ($timeline)
                        <div class="col-span-12 border-t border-line pt-3 md:col-span-4"><dt class="t-label text-muted">{{ __('site.service.timeline') }}</dt><dd class="mt-1">{{ $timeline }}</dd></div>
                    @endif
                    @if ($service->tags)
                        <div class="col-span-12 border-t border-line pt-3 md:col-span-4"><dt class="t-label text-muted">{{ __('site.service.technology') }}</dt><dd class="t-label mt-2 flex flex-wrap gap-x-4 gap-y-1">@foreach ($service->tags as $tag)<span>{{ $tag }}</span>@endforeach</dd></div>
                    @endif
                </dl>
            </div>
        @endif

        @if ($packages->isNotEmpty())
            <section>
                <x-section-label number="05" :label="__('site.packages.label')" :meta="'('.$packages->count().')'" />
                <div class="page py-12 md:py-20"><x-package-table :rows="$packages" /></div>
            </section>
        @endif
    </article>

    @if ($others->isNotEmpty())
        <x-service-rows :services="$others" number="06" :label="__('site.service.other')" />
    @endif

    @if ($contact)
        <section class="on-ink">
            <x-section-label number="—" :label="__('site.nav.contact')" />
            <div class="page py-16 md:py-28" data-reveal="group">
                <h2 class="t-h2 max-w-[18ch]"><x-lines :lines="[__('site.service.cta')]" /></h2>
                <p class="rise mt-8" data-delay="3"><x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </section>
    @endif
</x-layouts.app>
