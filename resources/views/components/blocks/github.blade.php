@props(['data', 'page', 'number'])

@php
    $repos = \App\Content\Site::github();
    $user = \App\Content\Site::setting('social.github_username');
@endphp

{{-- Public repositories, fetched and cached by the server: the visitor's browser never contacts GitHub. --}}
<section>
    <x-section-label :number="$number" :label="__('site.github.label')" :intro="__('site.github.intro')" :meta="'('.$repos->count().')'" />
    <div class="page section-body">
        <ul class="repo-grid">
            @foreach ($repos as $repo)
                <li data-reveal="group" data-delay="{{ min($loop->index, 5) }}">
                    <a href="{{ $repo['url'] }}" class="repo spot rise" rel="noopener" target="_blank">
                        <span class="flex items-start justify-between gap-4">
                            <span class="t-h3 break-all">{{ $repo['name'] }}</span>
                            <span class="repo-arrow" aria-hidden="true">↗</span>
                        </span>
                        @if ($repo['description'])
                            <span class="mt-3 block text-muted">{{ $repo['description'] }}</span>
                        @endif
                        <span class="t-label mt-6 flex flex-wrap gap-x-4 gap-y-1 text-muted">
                            @if ($repo['language'])<span>{{ $repo['language'] }}</span>@endif
                            @if ($repo['stars'])<span>★ {{ $repo['stars'] }}</span>@endif
                            @if ($repo['pushed_at'])<time datetime="{{ $repo['pushed_at'] }}">{{ __('site.github.updated') }} {{ \Illuminate\Support\Carbon::parse($repo['pushed_at'])->timezone(config('site.timezone'))->translatedFormat('M Y') }}</time>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-10" data-reveal="up"><x-link href="https://github.com/{{ $user }}" rel="me noopener" target="_blank" arrow>{{ __('site.github.all') }}</x-link></p>
    </div>
</section>
