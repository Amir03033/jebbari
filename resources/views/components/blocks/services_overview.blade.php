@props(['data', 'page', 'number'])

@php
    $services = \App\Content\Site::services(($data['mode'] ?? 'all') === 'selected' ? ($data['service_ids'] ?? []) : null);
@endphp

<x-service-rows :services="$services" :number="$number" :label="loc($data['label'] ?? null)" :intro="loc($data['intro'] ?? null)" />
