@props(['html'])

{{-- Editor text, reduced to a small safe set of tags and styled only by the site's own typography. --}}
<div {{ $attributes->class('prose-jebbari') }}>{!! \App\Support\HtmlSanitizer::clean($html) !!}</div>
