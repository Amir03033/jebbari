@php
    $hasIntro = \App\Content\BlockRenderer::blocks($page)->contains('type', 'intro');
    $start = $hasIntro ? 1 : 2;
    $next = str_pad((string) ($start + \App\Content\BlockRenderer::blocks($page, $start)->reject(fn ($b) => in_array($b['type'], ['meeting_line', 'reply_promise', 'image'], true))->count()), 2, '0', STR_PAD_LEFT);
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    @unless ($hasIntro)
        <x-page-header :page="$page" />
    @endunless
    <x-blocks :page="$page" :start="$start" />
    @include('partials.blog-listing', ['number' => $next])
</x-layouts.app>
