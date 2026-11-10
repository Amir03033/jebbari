@props(['services', 'number' => null, 'label' => null, 'intro' => null])

{{--
    Services as names at display scale. Hovering or focusing a row keeps it and
    lets the others recede; a soft surface rises behind it and the ring fills.
    No icons, no cards.
--}}
<section>
    @if ($label)
        <x-section-label :number="$number" :label="$label" :meta="'('.$services->count().')'" />
    @endif
    <div class="page py-14 md:py-24">
        @if ($intro)
            <p class="t-statement mb-12 max-w-[30ch] text-muted md:mb-20" data-reveal="up">{{ $intro }}</p>
        @endif

        <ul class="svc-list">
            @foreach ($services as $service)
                <li class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                    @unless ($loop->first)<span class="rule" aria-hidden="true"></span>@endunless
                    <a href="{{ \App\Support\Locale::route('services.show', ['slug' => $service->slugFor(app()->getLocale())]) }}" class="svc-item rise grid-12 items-center gap-y-5 py-8 md:py-11">
                        <span class="t-label col-span-12 text-muted md:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="svc-name col-span-12 md:col-span-6">{{ loc($service->title) }}</span>
                        <span class="col-span-12 flex items-center justify-between gap-6 md:col-span-5">
                            <span class="block max-w-[36ch]">
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
