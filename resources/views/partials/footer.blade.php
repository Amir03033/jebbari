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
    $address = $company['registered_address'] ?? null;
    $legalLine = collect([
        $company['legal_name'] ?? null,
        ! empty($company['kvk']) ? __('site.footer.kvk').' '.$company['kvk'] : null,
        ! empty($company['btw']) ? __('site.footer.btw').' '.$company['btw'] : null,
    ])->filter()->implode(' · ');
    $note = loc(Site::setting('header.footer_text'));
@endphp

<footer class="on-ink">
    <div class="page-wide border-t border-line"></div>

    <div class="page grid-12 gap-y-14 pb-16 pt-16 md:pt-24">
        <div class="col-span-12 md:col-span-5">
            <p class="t-label text-muted">{{ __('site.descriptor') }} · Zwolle &amp; Amsterdam</p>
            <p
                class="t-label mt-3"
                x-data="clock('{{ $now }}')"
            >{{ __('site.footer.time') }}: <time x-text="time">{{ $now }}</time></p>
            @if ($note)
                <p class="t-statement mt-8 max-w-[22ch]">{{ $note }}</p>
            @endif
        </div>

        <div class="col-span-6 md:col-span-2 md:col-start-7">
            <p class="t-label mb-4 text-muted">{{ __('site.footer.explore') }}</p>
            <ul class="grid gap-3">
                @foreach ($explore as $item)
                    <li><a href="{{ $item['url'] }}" class="link link-bare text-lg">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="col-span-6 md:col-span-4">
            @if ($email || $booking || $address)
                <p class="t-label mb-4 text-muted">{{ __('site.footer.contact') }}</p>
                <ul class="grid gap-3">
                    @if ($email)
                        <li><x-link href="mailto:{{ $email }}" class="text-lg">{{ $email }}</x-link></li>
                    @endif
                    @if ($booking)
                        <li><x-link :href="$booking" arrow class="text-lg">{{ __('site.cta.book') }}</x-link></li>
                    @endif
                    @if ($address)
                        <li class="whitespace-pre-line text-muted">{{ $address }}</li>
                    @endif
                </ul>
            @endif
            @if ($social)
                <p class="t-label mb-4 mt-10 text-muted">{{ __('site.footer.follow') }}</p>
                <ul class="grid gap-3">
                    @foreach ($social as $link)
                        <li><x-link :href="$link['url']" rel="me noopener" target="_blank" class="text-lg">{{ $link['label'] }}</x-link></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="page-wide">
        <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-t border-line py-6 text-sm">
            <p class="text-muted">{{ $legalLine }}</p>
            <p class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach ($legal as $item)
                    <a href="{{ $item['url'] }}" class="link link-bare text-muted">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                    <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                </a>
            </p>
        </div>
    </div>

    {{-- The last frame: the wordmark at the width of the screen, cropped by the bottom edge. --}}
    <div class="page-wide overflow-clip pt-6 md:pt-10" data-reveal="group" aria-hidden="true">
        <p class="footer-mark"><span class="line"><span class="line-inner">jebbari<span class="stop text-signal">.</span></span></span></p>
    </div>
</footer>
