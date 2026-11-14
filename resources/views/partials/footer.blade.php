@php
    use App\Content\Site;
    use App\Support\Locale;

    $now = now(config('site.timezone'))->format('H:i');
    $email = Site::email();
    $booking = Site::setting('contact.booking_url');
    $social = Site::social();
    $company = Site::setting('company', []);
    $explore = Site::navigation('header');
    $legal = Site::navigation('footer');
    $other = Locale::other();
    $a = config('site.address');
    $address = $a['street']."\n".$a['postal_code'].' '.$a['locality']."\n".$a['country'];
    $legalLine = collect([
        $company['legal_name'] ?? null,
        ! empty($company['kvk']) ? __('site.footer.kvk').' '.$company['kvk'] : null,
    ])->filter()->implode(' · ');
    $note = loc(Site::setting('header.footer_text'));
    $more = collect(['faq', 'now', 'uses', 'cv'])->map(fn ($key) => ($page = Site::page($key)) ? ['label' => loc($page->title), 'url' => Locale::url($key)] : null)->filter();
@endphp

{{-- The footer: brand and place, the ways through the site, the ways to reach out, the small print. --}}
<footer class="site-footer">
    <div class="page" data-reveal="group">
        <div class="relative"><span class="rule" aria-hidden="true"></span></div>

        <div class="grid-12 gap-y-14 pb-16 pt-14 md:pb-24 md:pt-20">
            <div class="rise col-span-12 md:col-span-4">
                <a href="{{ Locale::url('home') }}" class="footer-brand" aria-label="{{ config('site.name') }}, {{ __('site.nav.home') }}"><x-wordmark /></a>
                @if ($note)
                    <p class="t-statement mt-6 max-w-[24ch]">{{ $note }}</p>
                @endif
                <x-status class="mt-8 text-muted" />
                <p
                    class="t-label mt-3 text-muted"
                    x-data="clock('{{ $now }}')"
                >{{ __('site.footer.time') }}: <time class="text-fg" x-text="time">{{ $now }}</time></p>
                <x-live-status class="mt-10 max-w-[24rem]" />
            </div>

            <div class="rise col-span-6 md:col-span-2 md:col-start-6" data-delay="2">
                <p class="t-label mb-5 text-muted">{{ __('site.footer.explore') }}</p>
                <ul class="grid gap-3">
                    @foreach ($explore as $item)
                        <li><a href="{{ $item['url'] }}" class="link link-bare">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            @if ($more->isNotEmpty())
                <div class="rise col-span-6 md:col-span-2" data-delay="3">
                    <p class="t-label mb-5 text-muted">{{ __('site.footer.more') }}</p>
                    <ul class="grid gap-3">
                        @foreach ($more as $item)
                            <li><a href="{{ $item['url'] }}" class="link link-bare">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rise col-span-12 md:col-span-3 md:col-start-10" data-delay="4">
                @if ($email || $booking || $address)
                    <p class="t-label mb-5 text-muted">{{ __('site.footer.contact') }}</p>
                    <ul class="grid gap-3">
                        @if ($email)
                            <li><x-link href="mailto:{{ $email }}" class="break-all">{{ $email }}</x-link></li>
                        @endif
                        @if ($booking)
                            <li><x-link :href="$booking" arrow>{{ __('site.cta.book') }}</x-link></li>
                        @endif
                        @if ($address)
                            <li class="whitespace-pre-line text-muted">{{ $address }}</li>
                        @endif
                    </ul>
                @endif
                @if ($social)
                    <p class="t-label mb-5 mt-10 text-muted">{{ __('site.footer.follow') }}</p>
                    <ul class="grid gap-3">
                        @foreach ($social as $link)
                            <li><x-link :href="$link['url']" rel="me noopener" target="_blank" arrow>{{ $link['label'] }}</x-link></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="footer-base">
            <p class="text-muted">© {{ now(config('site.timezone'))->year }} {{ config('site.name') }}@if ($legalLine) · {{ $legalLine }}@endif</p>
            <p class="flex flex-wrap items-center gap-x-6 gap-y-2">
                @foreach ($legal as $item)
                    <a href="{{ $item['url'] }}" class="link link-bare text-muted">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="lang-switch t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                    <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                </a>
                <a href="#main" class="to-top t-label" data-to-top>{{ __('site.footer.top') }}<span class="to-top-arrow" aria-hidden="true">↑</span></a>
            </p>
        </div>
    </div>
</footer>
