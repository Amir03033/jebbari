@props(['post', 'eager' => false, 'featured' => false])

@php
    $locale = app()->getLocale();
    $date = $post->published_at?->timezone(config('site.timezone'));
@endphp

{{-- Blog card: rounded cover, mono meta, title, excerpt. The whole card is one link. --}}
<a href="{{ \App\Support\Locale::route('blog.show', ['slug' => $post->slugFor($locale)]) }}" {{ $attributes->class('post-card group block') }} data-reveal="group">
    @if ($post->cover)
        <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><div class="plate-img"><x-picture :asset="$post->cover" :sizes="$featured ? '(min-width: 1360px) 1264px, 100vw' : '(min-width: 1024px) 400px, (min-width: 768px) 50vw, 100vw'" :eager="$eager" /></div></div></div></div>
    @endif
    <p class="t-label rise mt-5 flex flex-wrap gap-x-4 text-muted">
        @if ($post->category)<span>{{ loc($post->category->name) }}</span>@endif
        @if ($date)<time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time>@endif
    </p>
    <h3 @class(['plate-title rise mt-2', 't-h3' => ! $featured, 't-h2 max-w-[20ch]' => $featured]) data-delay="1">{{ loc($post->title) }}</h3>
    <p @class(['rise mt-3 text-muted', 'max-w-[56ch]' => $featured]) data-delay="2">{{ loc($post->excerpt) }}</p>
</a>
