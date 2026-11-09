@php
    use App\Content\BlockRenderer;

    $hasIntro = BlockRenderer::blocks($page)->contains('type', 'intro');
    $start = $hasIntro ? 1 : 2;
    $next = $start + BlockRenderer::blocks($page, $start)->reject(fn ($b) => in_array($b['type'], ['meeting_line', 'reply_promise', 'image'], true))->count();
@endphp

<x-layouts.app :seo="$seo" :preview="$preview" header-theme="dark">
    @unless ($hasIntro)
        <x-page-header :page="$page" />
    @endunless
    <x-blocks :page="$page" :start="$start" />

    <section>
        <x-section-label :number="str_pad((string) $next, 2, '0', STR_PAD_LEFT)" :label="__('site.work.all')" :meta="'('.$projects->count().')'" />
        <div class="page py-16 md:py-28">
            @if ($projects->isEmpty())
                <p class="t-statement max-w-[24ch] text-muted">{{ __('site.empty.work') }}</p>
            @else
                <x-project-grid :projects="$projects" :eager-first="true" />
            @endif
        </div>
    </section>
</x-layouts.app>
