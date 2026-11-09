@php use App\Support\Locale; @endphp

@if ($categories->isNotEmpty())
    <nav aria-label="{{ __('site.blog.categories') }}" class="t-label page flex flex-wrap gap-x-6 gap-y-2 pb-8">
        <a href="{{ Locale::url('blog') }}" class="link link-bare @if (! $activeCategory) text-fg @else text-muted @endif" @if (! $activeCategory) aria-current="page" @endif>{{ __('site.blog.all') }}</a>
        @foreach ($categories as $cat)
            <a href="{{ Locale::route('blog.category', ['slug' => $cat->slugFor(app()->getLocale())]) }}" class="link link-bare @if ($activeCategory?->is($cat)) text-fg @else text-muted @endif" @if ($activeCategory?->is($cat)) aria-current="page" @endif>{{ loc($cat->name) }}</a>
        @endforeach
    </nav>
@endif

<section>
    <x-section-label :number="$number" :label="__('site.blog.articles')" :meta="'('.$posts->total().')'" />
    <div class="page py-12 md:py-20">
        @if ($posts->isEmpty())
            <p class="t-body text-muted">{{ __('site.empty.blog') }}</p>
        @else
            <div class="grid-12 gap-y-16 md:gap-y-24">
                @foreach ($posts as $post)
                    @if ($loop->first && $posts->currentPage() === 1 && $posts->count() > 1)
                        <x-post-card :post="$post" :eager="true" :featured="true" class="col-span-12" />
                    @else
                        <x-post-card :post="$post" :eager="$loop->first" class="col-span-12 md:col-span-6 lg:col-span-4" />
                    @endif
                @endforeach
            </div>

            @if ($posts->hasPages())
                <nav aria-label="{{ __('site.blog.pagination') }}" class="t-label mt-16 flex items-center justify-between border-t border-line pt-6">
                    @if ($posts->previousPageUrl())<a href="{{ $posts->previousPageUrl() }}" rel="prev" class="link link-bare">← {{ __('site.blog.newer') }}</a>@else<span></span>@endif
                    <span class="text-muted tabular-nums">{{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
                    @if ($posts->nextPageUrl())<a href="{{ $posts->nextPageUrl() }}" rel="next" class="link link-bare">{{ __('site.blog.older') }} →</a>@else<span></span>@endif
                </nav>
            @endif
        @endif
    </div>
</section>
