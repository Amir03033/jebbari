<x-layouts.app :seo="$seo" :preview="$preview">
    <x-page-opening number="01" :label="__('site.nav.blog')" :title="$title" :back="\App\Support\Locale::url('blog')" :back-label="__('site.nav.blog')" />
    @include('partials.blog-listing', ['number' => '02'])
</x-layouts.app>
