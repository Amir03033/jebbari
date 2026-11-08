@props(['services', 'number' => null, 'label' => null, 'intro' => null, 'heading' => null])

{{-- Services as a typographic list: number, name at heading size, one sentence and tags. No icons. --}}
<section>
    @if ($label)
        <x-section-label :number="$number" :label="$label" :meta="'('.$services->count().')'" />
    @endif
    <div class="page py-16 md:py-28">
        @if ($heading || $intro)
            <div class="grid-12 mb-12 gap-y-4 md:mb-20">
                @if ($heading)
                    <h2 class="t-h2 col-span-12 md:col-span-6">{{ $heading }}</h2>
                @endif
                @if ($intro)
                    <p class="t-body col-span-12 text-muted md:col-span-5 md:col-start-8">{{ $intro }}</p>
                @endif
            </div>
        @endif

        <ul>
            @foreach ($services as $service)
                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <a href="{{ \App\Support\Locale::route('services.show', ['slug' => $service->slugFor(app()->getLocale())]) }}" class="service-row rise grid-12 items-start gap-y-4 py-8 md:py-12">
                        <span class="t-label col-span-2 pt-2 text-muted md:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="t-h2 service-name col-span-10 md:col-span-5">{{ loc($service->title) }}</span>
                        <span class="col-span-12 md:col-span-5 md:col-start-8">
                            <span class="t-body block text-muted">{{ loc($service->summary) }}</span>
                            @if ($service->tags)
                                <span class="t-label mt-4 flex flex-wrap gap-x-4 gap-y-1 text-muted">
                                    @foreach ($service->tags as $tag)<span>{{ $tag }}</span>@endforeach
                                </span>
                            @endif
                            <span class="service-more t-label mt-4 inline-block">{{ __('site.cta.read_more') }} <span class="arrow" aria-hidden="true">→</span></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
