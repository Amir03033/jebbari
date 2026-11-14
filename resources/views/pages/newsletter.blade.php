<x-layouts.app :title="__('site.newsletter.'.$state.'_title')" :noindex="true">
    <section class="opening error-stage">
        <span class="opening-glow" aria-hidden="true"></span>
        <div class="page-wide relative" data-reveal="group">
            <p class="t-label rise text-muted" data-delay="1">{{ __('site.newsletter.label') }}</p>
            <h1 class="t-display mt-6"><x-lines :lines="[__('site.newsletter.'.$state.'_title')]" /></h1>
            <div class="grid-12 mt-10 md:mt-16">
                <p class="t-statement rise col-span-12 text-muted md:col-span-6 md:col-start-7" data-delay="4">{{ __('site.newsletter.'.$state.'_text') }}</p>
                <p class="rise col-span-12 mt-8 md:col-span-6 md:col-start-7" data-delay="5"><x-button :href="\App\Support\Locale::url('home')">{{ __('site.error.home') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </div>
    </section>
</x-layouts.app>
