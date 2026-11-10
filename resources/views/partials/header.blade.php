@php
    use App\Content\Site;
    use App\Support\Locale;

    $current = Locale::page();
    $links = Site::navigation('header');
    $other = Locale::other();
    $home = Locale::url('home');
    $dark = $headerTheme === 'dark';
@endphp

{{-- The header takes the colour of the section beneath it (motion.js keeps data-theme and .on-ink in step). --}}
<header
    x-data="siteHeader"
    @scroll.window="onScroll"
    @focusin="reveal"
    @keydown.escape.window="close"
    :data-hidden="isHidden"
    data-theme="{{ $headerTheme }}"
    class="site-header fixed inset-x-0 top-0 z-40 border-b border-line @if ($dark) on-ink @endif"
>
    <div class="page-wide flex h-16 items-center gap-4 md:h-20 lg:gap-6">
        <a href="{{ $home }}" class="flex items-baseline gap-3 text-2xl" aria-label="{{ config('site.name') }}, {{ __('site.nav.home') }}">
            <x-wordmark />
            <span class="t-label hidden whitespace-nowrap text-muted lg:inline">{{ __('site.descriptor') }}</span>
        </a>

        <x-status class="hidden text-muted xl:flex" />

        <nav aria-label="{{ __('site.nav.label') }}" class="ml-auto hidden items-center gap-6 whitespace-nowrap md:flex lg:gap-8">
            @foreach ($links as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="link link-bare relative text-base"
                    @if ($item['key'] && $current === $item['key']) aria-current="page" @endif
                >
                    @if ($item['key'] && $current === $item['key'])
                        <span class="dot-in absolute -left-3.5 top-1/2 size-1.5 -translate-y-1/2 rounded-full bg-signal" aria-hidden="true"></span>
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach

            <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
            </a>
            <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="{{ __('site.theme.toggle') }}" title="{{ __('site.theme.toggle') }}"><span class="theme-icon" aria-hidden="true"></span></button>
        </nav>

        <button
            type="button"
            x-ref="toggle"
            @click="toggle"
            :aria-expanded="expanded"
            aria-controls="mobile-menu"
            class="menu-toggle t-label ml-auto md:hidden"
        >
            <span class="grid" :data-open="expanded">
                <span class="menu-label" data-state="closed">{{ __('site.nav.menu') }}</span>
                <span class="menu-label" data-state="open">{{ __('site.nav.close') }}</span>
            </span>
            <span class="menu-icon" aria-hidden="true"><span></span><span></span></span>
        </button>
    </div>

    {{-- Full-screen menu: black, links at display size. --}}
    <div id="mobile-menu" :data-open="expanded" class="menu-panel on-ink fixed inset-x-0 bottom-0 top-16 overflow-y-auto md:hidden">
        <nav aria-label="{{ __('site.nav.label') }}" class="page-wide flex min-h-full flex-col justify-between pb-10 pt-8">
            <ul class="flex flex-col border-t border-line">
                @foreach ($links as $item)
                    <li class="menu-item">
                        <a
                            href="{{ $item['url'] }}"
                            @if ($loop->first) x-ref="firstLink" @endif
                            class="flex items-baseline gap-4 py-5 text-[clamp(2.25rem,11vw,4rem)] font-medium leading-none tracking-[-0.04em]"
                            @if ($item['key'] && $current === $item['key']) aria-current="page" @endif
                        >
                            <span class="t-label w-6 shrink-0 tracking-normal text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ $item['label'] }}</span>
                            @if ($item['key'] && $current === $item['key'])<span class="size-2.5 shrink-0 self-center rounded-full bg-signal" aria-hidden="true"></span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($contactUrl = Site::pageUrl('contact'))
                <p class="menu-item menu-item-plain mt-10"><x-button :href="$contactUrl" class="w-full">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            @endif

            <div class="mt-8 flex items-center justify-between">
                <x-status class="text-muted" />
                <span class="flex items-center gap-5">
                    <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                        <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                    </a>
                    <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="{{ __('site.theme.toggle') }}" title="{{ __('site.theme.toggle') }}"><span class="theme-icon" aria-hidden="true"></span></button>
                </span>
            </div>
        </nav>
    </div>
    <span class="scroll-line" aria-hidden="true"></span>
</header>

{{-- Spacer: the header is fixed so it can slide away. Dark pages start under it, so no spacer there. --}}
@unless ($dark)
    <div class="h-16 md:h-20" aria-hidden="true"></div>
@endunless
