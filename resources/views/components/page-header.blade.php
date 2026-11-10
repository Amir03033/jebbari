@props(['page', 'title' => null, 'label' => null, 'lead' => null])

{{-- Fallback opening for pages without an intro block: the page title as the single h1. --}}
<x-page-opening number="01" :label="$label ?? loc($page->title)" :title="$title ?? loc($page->title)" :lead="$lead" />
