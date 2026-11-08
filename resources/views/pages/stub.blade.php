@php($label = __('site.nav.'.$page))
<x-layouts.app :title="$label">
    <x-section-label number="01" :label="$label" />
    <div class="page section grid-12">
        <div class="col-span-12 md:col-span-9">
            <h1 class="t-display">{{ $label }}<span class="text-signal">.</span></h1>
        </div>
        <div class="col-span-12 mt-12 md:col-span-5 md:col-start-8">
            <p class="t-label mb-3 text-muted">{{ __('site.stub.label') }}</p>
            <p class="t-body text-muted">{{ __('site.stub.text') }}</p>
        </div>
    </div>
</x-layouts.app>
