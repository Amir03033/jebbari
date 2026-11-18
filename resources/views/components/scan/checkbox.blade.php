@props([
    'name',
    'label',
    'required' => false,
    'checked' => false,
    'error' => null,
])

@php($id = 'f-'.$name)

{{-- A consent checkbox in the style of x-field: label beside it, error beneath. --}}
<div {{ $attributes->class('flex flex-col gap-2') }}>
    <div class="check-row">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" class="check-control" @checked($checked) @required($required) @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
        <label for="{{ $id }}" class="text-sm leading-relaxed">{{ $label }}</label>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm font-medium">{{ $error }}</p>
    @endif
</div>
