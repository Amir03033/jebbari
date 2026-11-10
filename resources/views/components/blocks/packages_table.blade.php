@props(['data', 'page', 'number'])

@php
    use App\Content\PackagePricing;

    $rows = PackagePricing::rows();
@endphp

<section>
    <x-section-label :number="$number" :label="__('site.packages.label')" :meta="'('.$rows->count().')'" />
    <div class="page section-body">
        <x-package-table :rows="$rows" :show-service="true" />
    </div>
</section>
