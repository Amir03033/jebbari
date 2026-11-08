@php
    use App\Support\Locale;

    $now = now(config('site.timezone'))->format('H:i');
    $company = config('site.company');
    $email = config('site.email');
    $owner = fn (string $key) => '[[ OWNER: '.__('site.placeholder.'.$key).' ]]';
@endphp

<footer class="on-ink">
    <x-section-label number="—" :label="config('site.name')" />

    <div class="page grid-12 gap-y-12 pb-12 pt-10 md:pt-16">
        <div class="col-span-12 md:col-span-6">
            <p class="t-label mb-3 text-muted">{{ __('site.footer.email') }}</p>
            @if ($email)
                <x-link href="mailto:{{ $email }}" class="t-h3">{{ $email }}</x-link>
            @else
                <p class="t-h3">{{ $owner('email') }}</p>
            @endif
        </div>

        <div class="col-span-12 md:col-span-3">
            <p class="t-label mb-3 text-muted">{{ __('site.footer.elsewhere') }}</p>
            @forelse (config('site.social') as $label => $url)
                <p><x-link :href="$url">{{ $label }}</x-link></p>
            @empty
                <p class="text-muted">[[ OWNER: social links ]]</p>
            @endforelse
        </div>

        <div class="col-span-12 md:col-span-3">
            <p class="t-label mb-3 text-muted">{{ __('site.nav.language') }}</p>
            <p class="t-label">
                @foreach (Locale::all() as $alt)
                    <a href="{{ Locale::switchUrl($alt) }}" hreflang="{{ $alt }}" lang="{{ $alt }}" class="link link-bare" @if ($alt === app()->getLocale()) aria-current="true" @endif>{{ strtoupper($alt) }}</a>@unless ($loop->last) <span class="text-muted">/</span> @endunless
                @endforeach
            </p>
        </div>
    </div>

    <div class="border-t border-line">
        <div class="page grid-12 gap-y-6 py-6 text-sm">
            <p
                class="t-label col-span-12 md:col-span-4"
                x-data="clock('{{ $now }}')"
            >{{ __('site.footer.time') }}: <time x-text="time">{{ $now }}</time></p>

            <p class="col-span-12 text-muted md:col-span-5">
                {{ $company['legal_name'] ?? $owner('company') }}
                · {{ __('site.footer.kvk') }} {{ $company['kvk'] ?? $owner('kvk') }}
                · {{ __('site.footer.btw') }} {{ $company['btw'] ?? $owner('btw') }}
            </p>

            <p class="col-span-12 flex gap-6 md:col-span-3 md:justify-end">
                <span class="text-muted">{{ __('site.footer.privacy') }}</span>
                <span class="text-muted">{{ __('site.footer.terms') }}</span>
            </p>
        </div>
    </div>
</footer>
