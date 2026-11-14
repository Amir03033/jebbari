@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $email = Site::email();
    $booking = Site::setting('contact.booking_url');
    $bookingLabel = loc($data['booking_label'] ?? null);
    $contact = Site::pageUrl('contact');
@endphp

{{-- The ending: the question at display size, the email as a link you can read from across the room, the next step. --}}
<x-cta-panel :lines="$lines" :number="$number" id="cta-title">
    @if ($email)
        <p class="t-label mb-3 text-muted">{{ __('site.footer.email') }}</p>
        <x-link href="mailto:{{ $email }}" class="cta-email">{{ $email }}</x-link>
    @endif

    <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4">
        @if ($contact)
            <x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button>
        @endif
        @if ($booking && $bookingLabel)
            <x-link :href="$booking" arrow>{{ $bookingLabel }}</x-link>
        @endif
    </div>
</x-cta-panel>
