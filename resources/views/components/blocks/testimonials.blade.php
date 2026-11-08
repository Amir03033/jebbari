@props(['data', 'page', 'number'])

@php
    $mode = $data['mode'] ?? 'featured';
    $items = \App\Content\Site::testimonials($mode === 'featured', $mode === 'selected' ? ($data['testimonial_ids'] ?? []) : null);
@endphp

{{-- Real, permitted quotes only, shown as plain static text. No carousel. --}}
<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" />
    <div class="page py-16 md:py-28">
        <ul class="grid gap-16">
            @foreach ($items as $item)
                <li data-reveal="group">
                    <figure class="grid-12 gap-y-4">
                        <blockquote class="t-h2 rise col-span-12 md:col-span-9">“{{ loc($item->quote) }}”</blockquote>
                        <figcaption class="t-label rise col-span-12 text-muted" data-delay="3">{{ $item->name }}@if ($item->company), {{ $item->company }}@endif</figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
    </div>
</section>
