@props(['data', 'page', 'number'])

<x-page-opening
    :number="$number"
    :label="loc($data['label'] ?? null) ?: loc($page->title)"
    :title="loc($data['heading'])"
    :lead="loc($data['body'] ?? null)"
    :dark="$page->hasDarkOpening()"
/>
