@props(['data', 'page', 'number'])

@php
    $sent = session('newsletter') === 'sent';
    $error = $errors->getBag('newsletter')->first('newsletter_email');
@endphp

{{-- Newsletter sign-up. One field; the address only counts after it is confirmed by email. --}}
<section id="newsletter" class="section-tight scroll-mt-28" aria-labelledby="newsletter-title">
    <div class="page" data-reveal="group">
        <div class="newsletter card rise">
            <div>
                <p class="t-label text-muted">{{ $number }} / {{ __('site.newsletter.label') }}</p>
                <h2 id="newsletter-title" class="t-h2 mt-4 max-w-[16ch]">{{ __('site.newsletter.title') }}</h2>
                <p class="mt-4 max-w-[44ch] text-muted">{{ __('site.newsletter.text') }}</p>
            </div>

            @if ($sent)
                <p class="t-h3 self-end" role="status">{{ __('site.newsletter.sent') }}</p>
            @else
                <form method="post" action="{{ \App\Support\Locale::route('newsletter') }}" class="self-end" novalidate>
                    @csrf
                    <div class="sr-only" aria-hidden="true">
                        <label for="n-website">{{ __('site.contact.leave_empty') }}</label>
                        <input type="text" id="n-website" name="website" tabindex="-1" autocomplete="off" value="">
                    </div>
                    <label for="n-email" class="t-label">{{ __('site.contact.email') }}</label>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <input type="email" id="n-email" name="newsletter_email" required autocomplete="email" maxlength="190" value="{{ old('newsletter_email') }}"
                            class="field-control min-w-0 flex-1 basis-56" @if ($error) aria-invalid="true" aria-describedby="n-email-error" @endif>
                        <x-button type="submit">{{ __('site.newsletter.submit') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                    </div>
                    @if ($error)
                        <p id="n-email-error" class="mt-2 text-sm font-medium">{{ $error }}</p>
                    @endif
                    <p class="mt-3 text-sm text-muted">{{ __('site.newsletter.privacy') }}</p>
                </form>
            @endif
        </div>
    </div>
</section>
