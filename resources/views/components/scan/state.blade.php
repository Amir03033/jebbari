@props(['status'])

{{-- Quiet status marker: a filled dot (good), a ring (attention), the accent (action needed). --}}
<span {{ $attributes->class('scan-state t-label') }} data-state="{{ $status }}">
    <span class="scan-state-mark" aria-hidden="true"></span>{{ __('scan.state.'.$status) }}
</span>
