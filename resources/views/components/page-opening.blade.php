@props([
    'number' => '01',
    'label' => null,
    'title',
    'lead' => null,
    'dark' => false,
    'back' => null,
    'backLabel' => null,
    'meta' => null,
    'tall' => false,
])

{{--
    How every inner page opens: a stage with the title at display size. Dark
    stages carry the ring motif; light ones stay quiet. The title is the page's h1.
--}}
<section @class(['relative overflow-clip', 'on-ink' => $dark])>
    <div @class(['page-wide relative', 'pb-14 md:pb-24' => ! $tall, 'pb-40 md:pb-64' => $tall, 'pt-36 md:pt-56' => $dark, 'pt-10 md:pt-20' => ! $dark]) data-reveal="group">
        @if ($dark)
            <div class="rings opening-rings" aria-hidden="true"><span class="ring"></span><span class="ring"></span><span class="ring"></span><span class="ring-dot"></span></div>
        @endif

        <p class="t-label rise relative flex flex-wrap items-center gap-x-4 gap-y-1 text-muted" data-delay="1">
            @if ($back)<a href="{{ $back }}" class="link link-bare">← {{ $backLabel }}</a><span aria-hidden="true">·</span>@endif
            <span>{{ $number }} / {{ $label }}</span>
            @if ($meta)<span aria-hidden="true">·</span><span>{{ $meta }}</span>@endif
        </p>

        <h1 class="t-display relative mt-8 md:mt-12 md:max-w-[92%]"><x-lines :lines="[$title]" /></h1>

        @if ($lead)
            <div class="grid-12 relative mt-10 md:mt-20">
                <p class="t-statement rise col-span-12 md:col-span-6 md:col-start-7" data-delay="5">{{ $lead }}</p>
            </div>
        @endif
    </div>
</section>
