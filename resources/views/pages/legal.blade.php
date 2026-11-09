<x-layouts.app :seo="$seo" :preview="$preview">
    <x-page-header :page="$page" />
    <x-blocks :page="$page" :start="2" />

    <p class="t-label page pb-20 text-muted md:pb-32">{{ __('site.legal.updated') }}: <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->timezone(config('site.timezone'))->translatedFormat('j F Y') }}</time></p>
</x-layouts.app>
