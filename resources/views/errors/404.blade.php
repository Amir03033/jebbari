@php
    $segment = request()->segment(1);
    app()->setLocale(in_array($segment, config('site.locales'), true) ? $segment : config('site.default_locale'));
@endphp
<x-layouts.app :title="__('site.error.404_title')" :noindex="true">
    <x-section-label number="404" :label="__('site.error.404_title')" />
    <div class="page section grid-12" data-reveal="group">
        <div class="col-span-12 md:col-span-9">
            <h1 class="t-display"><x-lines :lines="[__('site.error.404_title')]" /></h1>
        </div>
        <div class="col-span-12 mt-12 md:col-span-5 md:col-start-8">
            <p class="t-body rise text-muted" data-delay="4">{{ __('site.error.404_text') }}</p>
            <p class="rise mt-8" data-delay="5"><x-link :href="\App\Support\Locale::url('home')" arrow>{{ __('site.error.home') }}</x-link></p>
        </div>
    </div>
</x-layouts.app>
