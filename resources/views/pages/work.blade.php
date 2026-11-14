@php
    use App\Content\BlockRenderer;

    $hasIntro = BlockRenderer::blocks($page)->contains('type', 'intro');
    $start = $hasIntro ? 1 : 2;
    $filters = $projects->flatMap(fn ($p) => $p->stack ?? [])->map(fn ($t) => trim((string) $t))->filter()->countBy()->filter(fn ($n) => $n > 1)->keys();
    $next = $start + BlockRenderer::blocks($page, $start)->reject(fn ($b) => in_array($b['type'], ['meeting_line', 'reply_promise', 'image'], true))->count();
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    @unless ($hasIntro)
        <x-page-header :page="$page" />
    @endunless
    <x-blocks :page="$page" :start="$start" />

    <section>
        <x-section-label :number="str_pad((string) $next, 2, '0', STR_PAD_LEFT)" :label="__('site.work.all')" :meta="'('.$projects->count().')'" />
        <div class="page section-body" data-filter-root>
            @if ($projects->isEmpty())
                <p class="t-statement max-w-[24ch] text-muted">{{ __('site.empty.work') }}</p>
            @else
                {{-- Filter by technology. Without script every project simply stays visible. --}}
                @if ($projects->count() > 3 && $filters->isNotEmpty())
                    <div class="filter-bar mb-10 md:mb-14" role="group" aria-label="{{ __('site.work.filter') }}">
                        <button type="button" class="filter-chip t-label" data-filter="" aria-pressed="true">{{ __('site.blog.all') }}</button>
                        @foreach ($filters as $tech)
                            <button type="button" class="filter-chip t-label" data-filter="{{ mb_strtolower($tech) }}" aria-pressed="false">{{ $tech }}</button>
                        @endforeach
                    </div>
                @endif
                <x-project-grid :projects="$projects" :eager-first="true" />
                <p class="t-statement mt-10 max-w-[28ch] text-muted" data-filter-empty hidden>{{ __('site.work.filter_empty') }}</p>
            @endif
        </div>
    </section>
</x-layouts.app>
