@php
    use App\Support\PreviewData;

    $states = ['draft' => 'Concept, niet gepubliceerd', 'live' => 'Gepubliceerd', 'live_changes' => 'Gepubliceerd met niet-gepubliceerde wijzigingen', 'scheduled' => 'Gepland'];
    $title = $record->t('title', $locale) ?? ($record->name ?? $record->key ?? 'Voorbeeld');
@endphp
<x-layouts.app :title="'Voorbeeld: '.$title" :noindex="true">
    <div class="border-b border-line bg-paper-2">
        <div class="page t-label flex flex-wrap items-center justify-between gap-x-8 gap-y-2 py-3">
            <p><span class="inline-block size-1.5 rounded-full bg-signal align-middle" aria-hidden="true"></span> Voorbeeld, niet openbaar</p>
            <p class="text-muted">{{ $states[$state] }}</p>
            <p><span class="text-muted">{{ strtoupper($locale) }}</span> / <a href="{{ $switchUrl }}" class="link link-bare" hreflang="{{ $other }}">{{ strtoupper($other) }}</a></p>
        </div>
    </div>

    <div class="page py-16 md:py-24">
        <p class="t-label mb-4 text-muted">Weergave van de inhoud. De definitieve opmaak volgt per pagina.</p>
        <h1 class="t-h2 mb-12 max-w-[22ch]">{{ $title }}</h1>
        {!! PreviewData::html($record, $locale) !!}
    </div>
</x-layouts.app>
