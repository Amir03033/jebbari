@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $email = Site::email();
    $booking = Site::setting('contact.booking_url');
    $bookingLabel = loc($data['booking_label'] ?? null);
    $contact = Site::pageUrl('contact');
@endphp

{{-- Dark close that continues into the footer. The email is the primary action when it exists. --}}
<section class="on-ink" aria-labelledby="cta-title">
    <x-section-label :number="$number" :label="__('site.nav.contact')" />
    <div class="page py-20 md:py-36" data-reveal="group">
        <h2 id="cta-title" class="t-display md:max-w-[80%]"><x-lines :lines="$lines" /></h2>

        <div class="rise mt-12 md:mt-20" data-delay="6">
            @if ($email)
                <p class="t-label mb-3 text-muted">{{ __('site.footer.email') }}</p>
                <x-link href="mailto:{{ $email }}" class="text-[clamp(1.5rem,4.2vw,3.75rem)] font-medium tracking-[-0.03em] break-all">{{ $email }}</x-link>
            @endif

            <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4">
                @if ($contact)
                    <x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                @endif
                @if ($booking && $bookingLabel)
                    <x-link :href="$booking" arrow>{{ $bookingLabel }}</x-link>
                @endif
            </div>
        </div>
    </div>
</section>
