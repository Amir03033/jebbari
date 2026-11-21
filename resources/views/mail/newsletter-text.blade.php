{{ $subject }}

{!! trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>', '</h2>', '</h3>'], ["\n\n", "\n", "\n", "\n\n", "\n\n"], $body)))) !!}
@if ($postUrl)

{{ __('site.newsletter.mail_read', [], $locale) }}: {{ $postUrl }}
@endif

--
{{ __('site.newsletter.mail_footer', [], $locale) }}
@if ($unsubscribeUrl)
{{ __('site.newsletter.mail_stop', [], $locale) }}: {{ $unsubscribeUrl }}
@endif
