@php
    use App\Support\Locale;
@endphp

<x-layouts.app :seo="$seo">
    <section class="opening error-stage">
        <span class="opening-glow" aria-hidden="true"></span>
        <div class="page-wide relative" data-reveal="group">
            <p class="t-label rise text-muted" data-delay="1">{{ __('leads.request.label') }}</p>
            <h1 class="t-display mt-6"><x-lines :lines="[__('leads.request.done_title')]" /></h1>
            <div class="grid-12 mt-10 md:mt-16">
                <div class="col-span-12 md:col-span-6 md:col-start-7">
                    <p class="t-statement rise text-muted" data-delay="4" role="status">{{ __('leads.request.done_text') }}</p>
                    <p class="rise mt-8 text-muted" data-delay="5">{{ __('leads.request.done_appointment') }}</p>
                    <p class="rise mt-4" data-delay="5"><x-button :href="Locale::url('appointment')">{{ __('leads.cta.appointment') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
