@props(['services', 'number' => null, 'label' => null, 'intro' => null, 'surface' => 'dark'])

{{--
    Services as names at display scale. Hovering or focusing a row keeps it and
    dims the others; the ring fills. No icons, no cards.
--}}
<section @class(['on-ink' => $surface === 'dark'])>
    @if ($label)
        <x-section-label :number="$number" :label="$label" :meta="'('.$services->count().')'" />
    @endif
    <div class="page py-20 md:py-36">
        @if ($intro)
            <p class="t-statement mb-16 max-w-[30ch] text-muted md:mb-28">{{ $intro }}</p>
        @endif

        <ul class="svc-list">
            @foreach ($services as $service)
                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    <span class="rule" aria-hidden="true"></span>
                    <a href="{{ \App\Support\Locale::route('services.show', ['slug' => $service->slugFor(app()->getLocale())]) }}" class="svc-item rise grid-12 items-end gap-y-6 py-10 md:py-14">
                        <span class="col-span-12 md:col-span-8">
                            <span class="t-label mb-4 block text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="svc-name">{{ loc($service->title) }}</span>
                        </span>
                        <span class="col-span-12 flex items-end justify-between gap-6 md:col-span-4">
                            <span class="block max-w-[34ch]">
                                <span class="block text-muted">{{ loc($service->summary) }}</span>
                                @if ($service->tags)
                                    <span class="t-label mt-4 flex flex-wrap gap-2 text-muted">
                                        @foreach ($service->tags as $tag)<span class="chip">{{ $tag }}</span>@endforeach
                                    </span>
                                @endif
                            </span>
                            <x-arrow-ring />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
