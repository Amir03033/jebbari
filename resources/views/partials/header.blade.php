@php
    use App\Content\Site;
    use App\Support\Locale;

    $current = Locale::page();
    $links = Site::navigation('header');
    $other = Locale::other();
    $home = Locale::url('home');
@endphp

{{--
    One quiet bar over the page: wordmark, navigation, language and theme. It
    is clear at the top, gains a soft frosted surface once the page moves, and
    slides away while reading down. A hairline follows the pointer between links.
--}}
<header
    x-data="siteHeader"
    @scroll.window="onScroll"
    @focusin="reveal"
    @keydown.escape.window="close"
    :data-hidden="isHidden"
    :data-scrolled="isScrolled"
    :data-menu="expanded"
    class="site-header fixed inset-x-0 top-0 z-40"
>
    <div class="site-header-bar page-wide">
        <a href="{{ $home }}" class="site-brand" aria-label="{{ config('site.name') }}, {{ __('site.nav.home') }}">
            <x-wordmark />
        </a>

        <nav aria-label="{{ __('site.nav.label') }}" class="site-nav hidden md:flex" data-nav>
            @foreach ($links as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="site-nav-link"
                    @if ($item['key'] && $current === $item['key']) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
            <span class="nav-line" aria-hidden="true"></span>
        </nav>

        <div class="site-tools hidden md:flex">
            <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="lang-switch t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
            </a>
            <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="{{ __('site.theme.toggle') }}" title="{{ __('site.theme.toggle') }}"><span class="theme-icon" aria-hidden="true"></span></button>
        </div>

        <button
            type="button"
            x-ref="toggle"
            @click="toggle"
            :aria-expanded="expanded"
            aria-controls="mobile-menu"
            class="menu-toggle t-label md:hidden"
        >
            <span class="grid" :data-open="expanded">
                <span class="menu-label" data-state="closed">{{ __('site.nav.menu') }}</span>
                <span class="menu-label" data-state="open">{{ __('site.nav.close') }}</span>
            </span>
            <span class="menu-icon" aria-hidden="true"><span></span><span></span></span>
        </button>
    </div>

    {{-- Full-screen menu: the page surface, links at display size. --}}
    <div id="mobile-menu" :data-open="expanded" class="menu-panel md:hidden">
        <nav aria-label="{{ __('site.nav.label') }}" class="page flex min-h-full flex-col justify-between pb-8 pt-6">
            <ul class="flex flex-col">
                @foreach ($links as $item)
                    <li class="menu-item">
                        <a
                            href="{{ $item['url'] }}"
                            @if ($loop->first) x-ref="firstLink" @endif
                            class="menu-link"
                            @if ($item['key'] && $current === $item['key']) aria-current="page" @endif
                        >
                            <span class="t-label w-6 shrink-0 text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ $item['label'] }}</span>
                            @if ($item['key'] && $current === $item['key'])<span class="size-2 shrink-0 self-center rounded-full bg-signal" aria-hidden="true"></span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div>
                @if ($contactUrl = Site::pageUrl('contact'))
                    <p class="menu-item menu-item-plain mt-10"><x-button :href="$contactUrl" class="w-full">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
                @endif

                <div class="mt-6 flex items-center justify-between gap-4">
                    <x-status class="text-muted" />
                    <span class="flex items-center gap-5">
                        <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="lang-switch t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                            <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                        </a>
                        <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="{{ __('site.theme.toggle') }}" title="{{ __('site.theme.toggle') }}"><span class="theme-icon" aria-hidden="true"></span></button>
                    </span>
                </div>
            </div>
        </nav>
    </div>
    <span class="scroll-line" aria-hidden="true"></span>
</header>

{{-- The header is fixed. Pages that open with a stage start beneath it; the rest get a spacer. --}}
@unless ($overlay)
    <div class="header-space" aria-hidden="true"></div>
@endunless
