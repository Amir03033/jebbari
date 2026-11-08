@props([
    'label',
    'value',
    'note' => null,
])

{{-- One row of a spec table. Numbers use tabular figures. --}}
<div {{ $attributes->class('grid-12 items-baseline border-t border-line py-4') }}>
    <dt class="col-span-12 md:col-span-5">{{ $label }}</dt>
    <dd class="col-span-8 font-medium tabular-nums md:col-span-4">{{ $value }}</dd>
    <dd class="t-label col-span-4 text-right text-muted md:col-span-3">{{ $note }}</dd>
</div>
