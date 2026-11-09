@php
    $segment = request()->segment(1);
    app()->setLocale(in_array($segment, config('site.locales'), true) ? $segment : config('site.default_locale'));
@endphp
<x-layouts.app :title="__('site.error.429_title')" :noindex="true" header-theme="dark">
    <section class="on-ink relative flex min-h-[88svh] items-end overflow-clip">
        <div class="rings opening-rings" aria-hidden="true"><span class="ring"></span><span class="ring"></span><span class="ring"></span><span class="ring-dot"></span></div>
        <div class="page-wide relative pb-16 pt-40 md:pb-28" data-reveal="group">
            <p class="t-label rise text-muted" data-delay="1">429</p>
            <h1 class="t-display mt-8"><x-lines :lines="[__('site.error.429_title')]" /></h1>
            <div class="grid-12 mt-12 md:mt-20">
                <p class="t-statement rise col-span-12 md:col-span-6 md:col-start-7" data-delay="4">{{ __('site.error.429_text') }}</p>
                <p class="rise col-span-12 mt-8 md:col-span-6 md:col-start-7" data-delay="5"><x-button :href="\App\Support\Locale::url('home')">{{ __('site.error.home') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </div>
    </section>
</x-layouts.app>
