@props(['title', 'kind' => null, 'large' => false])

{{--
    The visual for a project without a screenshot: its name set large on a dark
    panel with a fine grid and a soft light. Clearly typographic, never a fake
    interface; a real image from the CMS replaces it automatically.
--}}
<div {{ $attributes->class('tile on-ink') }}>
    <span class="tile-grid" aria-hidden="true"></span>
    <span class="tile-glow" aria-hidden="true"></span>
    <p class="t-label relative text-muted">{{ $kind }}</p>
    <p class="{{ $large ? 'plate-name-xl' : 'plate-name' }} relative">{{ $title }}</p>
</div>
