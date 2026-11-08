@php($open = config('site.taking_projects'))
<p {{ $attributes->class('t-label flex items-center gap-2') }}>
    <span class="size-1.5 shrink-0 rounded-full bg-signal" aria-hidden="true"></span>
    <span>{{ $open ? __('site.status.open') : __('site.status.closed') }}</span>
</p>
