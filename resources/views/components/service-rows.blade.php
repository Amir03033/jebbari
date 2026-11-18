@props(['services', 'number' => null, 'label' => null, 'intro' => null])

{{--
    Services as a calm two-column index (one column on phones): number, name,
    one line and a few tags, each separated by a hairline. Pointing at or
    focusing one lets a soft surface rise behind it and the others recede.
    No icons, no cards. Each name is an h3 under the section's h2.
--}}
<section>
    @if ($label)
        <x-section-label :number="$number" :label="$label" :intro="$intro" :meta="'('.$services->count().')'" />
    @endif
    <div class="page section-body">
        <ul class="svc-list svc-grid">
            @foreach ($services as $service)
                <li class="svc-cell" data-reveal="group" data-delay="{{ $loop->index % 2 }}">
                    <span class="rule" aria-hidden="true"></span>
                    <a href="{{ \App\Support\Locale::route('services.show', ['slug' => $service->slugFor(app()->getLocale())]) }}" class="svc-item svc-card rise">
                        <span class="svc-top">
                            <span class="t-label text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <x-arrow-ring />
                        </span>
                        <h3 class="svc-title">{{ loc($service->title) }}</h3>
                        @if (loc($service->summary))
                            <span class="svc-summary text-muted">{{ loc($service->summary) }}</span>
                        @endif
                        @if ($service->tags)
                            <span class="t-label svc-tags text-muted">
                                @foreach (collect($service->tags)->take(4) as $tag)<span class="chip">{{ $tag }}</span>@endforeach
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
