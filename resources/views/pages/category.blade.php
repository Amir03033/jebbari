<x-layouts.app :seo="$seo" :preview="$preview">
    <section>
        <x-section-label number="01" :label="__('site.nav.blog')" />
        <div class="page pb-8 pt-16 md:pb-12 md:pt-28" data-reveal="group">
            <h1 class="t-display md:max-w-[85%]"><x-lines :lines="[$title]" /></h1>
        </div>
    </section>
    @include('partials.blog-listing', ['number' => '02'])
</x-layouts.app>
