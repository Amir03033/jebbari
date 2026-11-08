<p {{ $attributes->class('t-label flex items-center gap-2') }}>
    <span class="size-1.5 shrink-0 rounded-full bg-signal" aria-hidden="true"></span>
    <span>{{ \App\Content\Site::statusText() }}</span>
</p>
