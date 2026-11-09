@php($hasIntro = \App\Content\BlockRenderer::blocks($page)->contains('type', 'intro'))

<x-layouts.app :seo="$seo" :preview="$preview" :header-theme="$page->hasDarkOpening() ? 'dark' : 'light'">
    @unless ($hasIntro)
        <x-page-header :page="$page" />
    @endunless
    <x-blocks :page="$page" :start="$hasIntro ? 1 : 2" />
</x-layouts.app>
