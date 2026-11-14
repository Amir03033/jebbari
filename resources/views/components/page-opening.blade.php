@props([
    'number' => '01',
    'label' => null,
    'title',
    'lead' => null,
    'back' => null,
    'backLabel' => null,
    'meta' => null,
])

{{--
    How every inner page opens: a calm stage under the header with the title at
    display size (the page's h1), a soft light behind it and, when there is one,
    the lead beside it.
--}}
<section class="opening">
    <span class="opening-glow" aria-hidden="true"></span>
    <div class="page-wide relative" data-reveal="group">
        <p class="t-label rise flex flex-wrap items-center gap-x-4 gap-y-1 text-muted" data-delay="1">
            @if ($back)<a href="{{ $back }}" class="back-link">← {{ $backLabel }}</a><span aria-hidden="true">·</span>@endif
            <span>{{ $number }} / {{ $label }}</span>
            @if ($meta)<span aria-hidden="true">·</span><span>{{ $meta }}</span>@endif
        </p>

        <h1 class="t-display mt-6 md:mt-10 md:max-w-[88%]"><x-lines :lines="[$title]" /></h1>

        @if ($lead)
            <div class="grid-12 mt-10 md:mt-16">
                <p class="t-statement rise col-span-12 text-muted md:col-span-6 md:col-start-7" data-delay="5">{{ $lead }}</p>
            </div>
        @endif
    </div>
</section>
