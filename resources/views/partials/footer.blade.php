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
    <x-section-label number="—" :label="config('site.name').' · '.__('site.descriptor')" />

    <div class="page grid-12 gap-y-12 pb-12 pt-10 md:pt-16">
        <div class="col-span-12 md:col-span-5">
            <p class="text-4xl font-medium tracking-[-0.03em]"><x-wordmark /></p>
            <p class="t-label mt-3 text-muted">{{ __('site.descriptor') }} · Zwolle &amp; Amsterdam</p>
            @if ($note)
                <p class="mt-6 max-w-[44ch] text-muted">{{ $note }}</p>
            @endif
        </div>

        <div class="col-span-6 md:col-span-2 md:col-start-7">
            <p class="t-label mb-3 text-muted">{{ __('site.footer.explore') }}</p>
            <ul class="grid gap-2">
                @foreach ($explore as $item)
                    <li><a href="{{ $item['url'] }}" class="link link-bare">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="col-span-6 md:col-span-4">
            @if ($email || $booking || $address)
                <p class="t-label mb-3 text-muted">{{ __('site.footer.contact') }}</p>
                <ul class="grid gap-2">
                    @if ($email)
                        <li><x-link href="mailto:{{ $email }}">{{ $email }}</x-link></li>
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
                <p class="t-label mb-3 mt-8 text-muted">{{ __('site.footer.follow') }}</p>
                <ul class="grid gap-2">
                    @foreach ($social as $link)
                        <li><x-link :href="$link['url']" rel="me noopener" target="_blank">{{ $link['label'] }}</x-link></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="border-t border-line">
        <div class="page grid-12 gap-y-6 py-6 text-sm">
            <p
                class="t-label col-span-12 md:col-span-4"
                x-data="clock('{{ $now }}')"
            >{{ __('site.footer.time') }}: <time x-text="time">{{ $now }}</time></p>

            @if ($legalLine)
                <p class="col-span-12 text-muted md:col-span-5">{{ $legalLine }}</p>
            @endif

            <p class="col-span-12 flex flex-wrap gap-x-6 gap-y-2 md:col-span-3 md:justify-end @if (! $legalLine) md:col-start-5 md:col-span-4 @endif">
                @foreach ($legal as $item)
                    <a href="{{ $item['url'] }}" class="link link-bare text-muted">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                    <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                </a>
            </p>
        </div>
    </div>
</footer>
