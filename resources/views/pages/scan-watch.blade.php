@php use App\Support\Locale; @endphp
{{-- Website watch confirmed or stopped (links from the watch emails). --}}
<x-layouts.app :seo="$seo">
    <x-page-opening number="01" :label="__('scan.watch.label')" :title="$title" :lead="$text" />
    <section>
        <div class="page pb-24">
            <x-button :href="Locale::url('home')" variant="secondary">{{ __('site.error.home') }}<span class="arrow" aria-hidden="true">→</span></x-button>
        </div>
    </section>
</x-layouts.app>
