{{ $title }}

@foreach ($paragraphs ?? [] as $paragraph)
{{ $paragraph }}

@endforeach
@foreach ($rows ?? [] as $key => $value)
- {{ $key }}: {{ $value }}
@endforeach
@if (! empty($rows))

@endif
@if (! empty($button))
{{ $button['label'] }}: {{ $button['url'] }}

@endif
@if (! empty($box))
{{ $box['text'] }}
{{ $box['url'] }}

@endif
@if (! empty($footer))
--
{{ $footer }}
@endif
