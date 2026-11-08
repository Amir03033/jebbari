@php
    use App\Support\Locale;

    $current = Locale::page();
    $links = ['work', 'services', 'about', 'contact'];
    $other = collect(Locale::all())->first(fn ($l) => $l !== app()->getLocale());
@endphp

<header
    x-data="siteHeader"
    @scroll.window="onScroll"
    @focusin="reveal"
    @keydown.escape.window="close"
    :data-hidden="isHidden"
    class="site-header fixed inset-x-0 top-0 z-40 border-b border-line bg-paper"
>
    <div class="page flex h-16 items-center gap-6 md:h-20">
        <a href="{{ $current ? Locale::url('home') : url('/') }}" class="text-2xl" aria-label="{{ config('site.name') }}, {{ __('site.nav.home') }}">
            <x-wordmark />
        </a>

        <x-status class="hidden text-muted lg:flex" />

        <nav aria-label="{{ __('site.nav.label') }}" class="ml-auto hidden items-center gap-8 md:flex">
            @foreach ($links as $page)
                <a
                    href="{{ Locale::url($page) }}"
                    class="link link-bare relative text-base"
                    @if ($current === $page) aria-current="page" @endif
                >
                    @if ($current === $page)
                        <span class="dot-in absolute -left-3.5 top-1/2 size-1.5 -translate-y-1/2 rounded-full bg-signal" aria-hidden="true"></span>
                    @endif
                    {{ __('site.nav.'.$page) }}
                </a>
            @endforeach

            <a href="{{ Locale::switchUrl($other) }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
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

    {{-- Full-screen menu. Same links at display size. --}}
    <div id="mobile-menu" :data-open="expanded" class="menu-panel fixed inset-x-0 bottom-0 top-16 overflow-y-auto bg-paper md:hidden">
        <nav aria-label="{{ __('site.nav.label') }}" class="page flex min-h-full flex-col justify-between pb-10 pt-8">
            <ul class="flex flex-col border-t border-line">
                @foreach ($links as $page)
                    <li class="menu-item">
                        <a
                            href="{{ Locale::url($page) }}"
                            @if ($loop->first) x-ref="firstLink" @endif
                            class="t-h2 flex items-center gap-4 py-5"
                            @if ($current === $page) aria-current="page" @endif
                        >
                            @if ($current === $page)<span class="size-2 rounded-full bg-signal" aria-hidden="true"></span>@endif
                            {{ __('site.nav.'.$page) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10 flex items-center justify-between">
                <x-status class="text-muted" />
                <a href="{{ Locale::switchUrl($other) }}" hreflang="{{ $other }}" lang="{{ $other }}" class="link link-bare t-label" aria-label="{{ __('site.nav.language') }}: {{ strtoupper($other) }}">
                    <span class="text-muted">{{ strtoupper(app()->getLocale()) }}</span> / {{ strtoupper($other) }}
                </a>
            </div>
        </nav>
    </div>
</header>

{{-- Spacer: the header is fixed so it can slide away. --}}
<div class="h-16 md:h-20" aria-hidden="true"></div>
