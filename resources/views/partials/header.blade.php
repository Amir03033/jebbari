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
        </nav>

        <button
            type="button"
            x-ref="toggle"
            @click="toggle"
            :aria-expanded="expanded"
            aria-controls="mobile-menu"
            class="t-label ml-auto min-h-11 px-1 md:hidden"
        >
            <span class="grid" :data-open="expanded">
                <span class="menu-label" data-state="closed">{{ __('site.nav.menu') }}</span>
                <span class="menu-label" data-state="open">{{ __('site.nav.close') }}</span>
            </span>
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
                            class="flex items-center gap-4 py-5 text-[clamp(2.5rem,12vw,4.5rem)] font-medium leading-none tracking-[-0.04em]"
                            @if ($item['key'] && $current === $item['key']) aria-current="page" @endif
                        >
                            @if ($item['key'] && $current === $item['key'])<span class="size-2.5 rounded-full bg-signal" aria-hidden="true"></span>@endif
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10 flex items-center justify-between">
                <x-status class="text-muted" />
                <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                    <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                </a>
            </div>
        </nav>
    </div>
    <span class="scroll-line" aria-hidden="true"></span>
</header>

{{-- Spacer: the header is fixed so it can slide away. Dark pages start under it, so no spacer there. --}}
@unless ($dark)
    <div class="h-16 md:h-20" aria-hidden="true"></div>
@endunless
