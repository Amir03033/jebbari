@php
    use App\Content\BlockRenderer;
    use App\Content\Site;

    $about = Site::page('about');
    $intro = $about ? BlockRenderer::blocks($about)->firstWhere('type', 'intro') : null;
    $bio = loc($intro['data']['body'] ?? null);
@endphp

{{-- Who wrote this: the name, a line from the About page and the way to it. Nothing is shown that is not published there. --}}
<aside {{ $attributes->class('author card') }} aria-label="{{ __('site.share.author') }}">
    <span class="author-mark" aria-hidden="true">j<span class="dot text-signal"></span></span>
    <div>
        <p class="t-label text-muted">{{ __('site.share.author') }}</p>
        <p class="t-h3 mt-2">{{ config('site.name') }}</p>
        <p class="mt-1 text-muted">{{ $bio ?: __('site.descriptor') }}</p>
        @if ($about)
            <p class="mt-4"><x-link :href="\App\Support\Locale::url('about')" arrow>{{ __('site.cta.about') }}</x-link></p>
        @endif
    </div>
</aside>
