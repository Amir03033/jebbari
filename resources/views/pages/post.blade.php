@php
    use App\Support\Locale;

    $date = $post->published_at?->timezone(config('site.timezone'));
    $locale = app()->getLocale();
    $contact = \App\Content\Site::pageUrl('contact');
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-section-label number="01" :label="__('site.nav.blog')" :meta="$post->category ? loc($post->category->name) : null" />

        <header class="page pb-10 pt-12 md:pb-16 md:pt-20" data-reveal="group">
            <nav aria-label="{{ __('site.breadcrumb') }}" class="t-label rise mb-8 text-muted"><a href="{{ Locale::url('blog') }}" class="link link-bare">← {{ __('site.nav.blog') }}</a></nav>
            <h1 class="t-display md:max-w-[90%]"><x-lines :lines="[$title]" /></h1>
            <p class="t-label rise mt-8 flex flex-wrap gap-x-6 gap-y-1 text-muted" data-delay="5">
                @if ($date)<time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time>@endif
                <span>{{ trans_choice('site.blog.reading', $minutes, ['count' => $minutes]) }}</span>
                @if ($post->category)<a href="{{ Locale::route('blog.category', ['slug' => $post->category->slugFor($locale)]) }}" class="link link-bare">{{ loc($post->category->name) }}</a>@endif
            </p>
        </header>

        @if ($post->cover)
            <div class="page pb-12 md:pb-20" data-reveal="group">
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
            <x-section-label number="—" :label="__('site.nav.contact')" />
            <div class="page py-16 md:py-28" data-reveal="group">
                <h2 class="t-h2 max-w-[18ch]"><x-lines :lines="[__('site.blog.cta')]" /></h2>
                <p class="rise mt-8" data-delay="3"><x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </section>
    @endif
</x-layouts.app>
