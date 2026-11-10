@props(['data', 'page', 'number'])

@php
    use App\Content\Site;

    $lines = collect(preg_split('/\R/', (string) loc($data['headline'])))->map(fn ($l) => trim($l))->filter()->values()->all();
    $email = Site::email();
    $booking = Site::setting('contact.booking_url');
    $bookingLabel = loc($data['booking_label'] ?? null);
    $contact = Site::pageUrl('contact');
@endphp

{{-- The full stop becomes the night: a dark circle grows from the orange dot until it fills the screen, and the closing section continues in it. --}}
<div class="dot-bridge" data-scroll="cross" aria-hidden="true"><span class="dot-bridge-fill"></span><span class="dot-bridge-dot"></span></div>

{{-- The ending: the question at the width of the screen, the email at display size, rings behind. It flows into the footer. --}}
<section class="cta on-ink" aria-labelledby="cta-title" data-scroll="enter">
    <x-section-label :number="$number" :label="__('site.nav.contact')" />
    <div class="page-wide relative py-24 md:py-40" data-reveal="group">
        <div class="rings" aria-hidden="true"><span class="ring"></span><span class="ring"></span><span class="ring"></span><span class="ring-dot"></span></div>

        <h2 id="cta-title" class="t-display relative md:max-w-[85%]"><x-lines :lines="$lines" /></h2>

        <div class="rise relative mt-16 md:mt-28" data-delay="6">
            @if ($email)
                <p class="t-label mb-4 text-muted">{{ __('site.footer.email') }}</p>
                <x-link href="mailto:{{ $email }}" class="cta-email">{{ $email }}</x-link>
            @endif

            <div class="mt-12 flex flex-wrap items-center gap-x-8 gap-y-4">
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
