{{-- Bot traps shared by the lead forms: time stamp, honeypot, and Turnstile when it is switched on. --}}
<input type="hidden" name="_t" value="{{ \App\Leads\SpamGuard::stamp() }}">
<div class="sr-only" aria-hidden="true">
    <label for="f-website">{{ __('site.contact.leave_empty') }}</label>
    <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off" value="">
</div>
@if (\App\Scan\Turnstile::enabled())
    <div class="cf-turnstile" data-sitekey="{{ \App\Scan\Turnstile::siteKey() }}" data-language="{{ app()->getLocale() }}"></div>
    @push('head')
        <script src="{{ \App\Scan\Turnstile::HOST }}/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
