@php
    use App\Support\Locale;

    $date = $post->published_at?->timezone(config('site.timezone'));
    $locale = app()->getLocale();
    $contact = \App\Content\Site::pageUrl('contact');
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-page-opening number="01" :label="__('site.nav.blog')" :title="$title" :back="Locale::url('blog')" :back-label="__('site.nav.blog')" :meta="$post->category ? loc($post->category->name) : null" />

        <p class="t-label page-wide flex flex-wrap gap-x-6 gap-y-1 pb-10 text-muted md:pb-14" data-reveal="up">
            @if ($date)<time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time>@endif
            <span>{{ trans_choice('site.blog.reading', $minutes, ['count' => $minutes]) }}</span>
            @if ($post->category)<a href="{{ Locale::route('blog.category', ['slug' => $post->category->slugFor($locale)]) }}" class="link link-bare">{{ loc($post->category->name) }}</a>@endif
        </p>

        @if ($post->cover)
            <div class="page-wide pb-12 md:pb-24" data-reveal="group">
                <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$post->cover" sizes="100vw" :eager="true" /></div></div></div>
            </div>
        @endif

        {{-- The text, with a contents list that follows along and marks where you are. --}}
        <div class="page pb-16 md:pb-28" data-article data-scroll="read">
            <span class="read-line" aria-hidden="true"></span>
            <div class="grid-12 gap-y-10">
                @if (count($toc['items']) > 1)
                    <nav class="toc col-span-12 lg:col-span-3" aria-label="{{ __('site.blog.toc') }}" data-toc>
                        <p class="t-label mb-4 text-muted">{{ __('site.blog.toc') }}</p>
                        <ol>
                            @foreach ($toc['items'] as $item)
                                <li><a href="#{{ $item['id'] }}" class="toc-link">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                @endif
                <div @class(['col-span-12', 'lg:col-span-8 lg:col-start-5' => count($toc['items']) > 1, 'md:col-span-8 md:col-start-3' => count($toc['items']) <= 1])>
                    @if (loc($post->excerpt))
                        <p class="t-statement mb-10">{{ loc($post->excerpt) }}</p>
                    @endif
                    <div class="prose-jebbari">{!! $toc['html'] !!}</div>

                    <x-share class="mt-14" :url="$seo['canonical']" :title="$title" />
                    <x-author class="mt-10" />
                </div>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section>
            <x-section-label number="02" :label="__('site.blog.related')" />
            <div class="page section-body">
                <div class="grid-12 gap-y-16">
                    @foreach ($related as $item)
                        <x-post-card :post="$item" class="col-span-12 md:col-span-6 lg:col-span-4" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($contact)
        <x-cta-panel :lines="[__('site.blog.cta')]">
            <x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button>
        </x-cta-panel>
    @endif
</x-layouts.app>
