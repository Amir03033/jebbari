@php
    use App\Support\Locale;

    $date = $post->published_at?->timezone(config('site.timezone'));
    $locale = app()->getLocale();
    $contact = \App\Content\Site::pageUrl('contact');
    $aboutUrl = \App\Content\Site::pageUrl('about');
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-page-opening number="01" :label="__('site.nav.blog')" :title="$title" :back="Locale::url('blog')" :back-label="__('site.nav.blog')" :meta="$post->category ? loc($post->category->name) : null" />

        <p class="t-label page flex flex-wrap gap-x-6 gap-y-1 pb-10 text-muted md:pb-14">
            <span>{{ __('site.blog.by') }} @if ($aboutUrl)<a href="{{ $aboutUrl }}" class="link link-bare" rel="author">{{ config('site.founder.name') }}</a>@else{{ config('site.founder.name') }}@endif</span>
            @if ($date)<time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time>@endif
            <span>{{ trans_choice('site.blog.reading', $minutes, ['count' => $minutes]) }}</span>
            @if ($post->category)<a href="{{ Locale::route('blog.category', ['slug' => $post->category->slugFor($locale)]) }}" class="link link-bare">{{ loc($post->category->name) }}</a>@endif
        </p>

        @if ($post->cover)
            <div class="page pb-12 md:pb-24" data-reveal="group">
                <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$post->cover" sizes="(min-width: 1360px) 1264px, 100vw" :eager="true" /></div></div></div>
            </div>
        @endif

        <div class="page pb-16 md:pb-28">
            <div class="grid-12">
                @if (loc($post->excerpt))
                    <p class="t-h3 col-span-12 mb-10 md:col-span-8 md:col-start-3">{{ loc($post->excerpt) }}</p>
                @endif
                <x-rich-text :html="loc($post->body)" class="col-span-12 md:col-span-8 md:col-start-3" />
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section>
            <x-section-label number="02" :label="__('site.blog.related')" />
            <div class="page py-16 md:py-28">
                <div class="grid-12 gap-y-16">
                    @foreach ($related as $item)
                        <x-post-card :post="$item" class="col-span-12 md:col-span-6 lg:col-span-4" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($contact)
        <section class="on-ink">
            <div class="page py-24 md:py-40" data-reveal="group">
                <h2 class="t-display max-w-[16ch]"><x-lines :lines="[__('site.blog.cta')]" /></h2>
                <p class="rise mt-10" data-delay="3"><x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </section>
    @endif
</x-layouts.app>
