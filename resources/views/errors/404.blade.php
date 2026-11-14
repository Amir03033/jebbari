@php
    $segment = request()->segment(1);
    app()->setLocale(in_array($segment, config('site.locales'), true) ? $segment : config('site.default_locale'));
    $links = \App\Content\Site::navigation('header');
@endphp
<x-layouts.app :title="__('site.error.404_title')" :noindex="true">
    <section class="opening error-stage">
        <span class="opening-glow" aria-hidden="true"></span>
        <p class="error-code" aria-hidden="true">404</p>
        <div class="page-wide relative" data-reveal="group">
            <p class="t-label rise text-muted" data-delay="1">404</p>
            <h1 class="t-display mt-6"><x-lines :lines="[__('site.error.404_title')]" /></h1>
            <div class="grid-12 mt-10 gap-y-10 md:mt-16">
                <div class="col-span-12 md:col-span-6 md:col-start-7">
                    <p class="t-statement rise text-muted" data-delay="4">{{ __('site.error.404_text') }}</p>
                    <p class="rise mt-8 flex flex-wrap gap-3" data-delay="5">
                        <x-button :href="\App\Support\Locale::url('home')">{{ __('site.error.home') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                        <x-button variant="secondary" type="button" data-cmd-open>{{ __('site.command.title') }}</x-button>
                    </p>
                    @if ($links->isNotEmpty())
                        <nav class="rise mt-12" data-delay="6" aria-label="{{ __('site.error.try') }}">
                            <p class="t-label mb-4 text-muted">{{ __('site.error.try') }}</p>
                            <ul class="error-links">
                                @foreach ($links as $item)
                                    <li><a href="{{ $item['url'] }}" class="error-link"><span>{{ $item['label'] }}</span><span class="arrow" aria-hidden="true">→</span></a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
