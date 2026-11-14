@props(['data', 'page', 'number'])

@php($items = collect($data['items'] ?? [])->filter(fn ($i) => loc($i['question'] ?? null) && loc($i['answer'] ?? null))->values())

{{-- Questions as an accordion (native details, so it works without script); one open at a time. --}}
<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :intro="loc($data['heading'] ?? null)" :meta="'('.$items->count().')'" />
    <div class="page section-body">
        <div class="faq">
            @foreach ($items as $item)
                <details class="faq-item" name="faq-{{ $number }}" data-reveal="group" data-delay="{{ min($loop->index, 4) }}">
                    <summary class="faq-q rise">
                        <span class="t-h3">{{ loc($item['question']) }}</span>
                        <span class="faq-icon" aria-hidden="true"></span>
                    </summary>
                    <p class="faq-a text-muted">{{ loc($item['answer']) }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
