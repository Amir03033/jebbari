@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    $name = \App\Support\Settings::get('seo.site_name') ?: config('site.name');
@endphp
{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $name }} · {{ __('site.nav.blog') }}</title>
        <link>{{ Locale::url('blog') }}</link>
        <description>{{ __('site.meta_description') }}</description>
        <language>{{ $locale }}</language>
        <atom:link href="{{ Locale::route('blog.feed') }}" rel="self" type="application/rss+xml"/>
        @foreach ($posts as $post)
            <item>
                <title>{{ loc($post->title) }}</title>
                <link>{{ Locale::route('blog.show', ['slug' => $post->slugFor($locale)]) }}</link>
                <guid isPermaLink="true">{{ Locale::route('blog.show', ['slug' => $post->slugFor($locale)]) }}</guid>
                @if ($post->published_at)<pubDate>{{ $post->published_at->toRssString() }}</pubDate>@endif
                <description>{{ loc($post->excerpt) }}</description>
            </item>
        @endforeach
    </channel>
</rss>
