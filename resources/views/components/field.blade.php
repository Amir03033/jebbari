@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'required' => false,
    'options' => null,
    'value' => null,
    'autocomplete' => null,
    'maxlength' => null,
    'rows' => 6,
])

@php
    $id = 'f-'.$name;
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $control = [
        'id' => $id,
        'name' => $name,
        'aria-invalid' => $error ? 'true' : null,
        'aria-describedby' => $describedBy ?: null,
        'required' => $required,
        'autocomplete' => $autocomplete,
        'maxlength' => $maxlength,
    ];
@endphp

<div {{ $attributes->class('flex flex-col gap-2') }}>
    <label for="{{ $id }}" class="t-label">
        {{ $label }}@unless ($required)<span class="text-muted"> ({{ __('site.field.optional') }})</span>@endunless
    </label>

    @if ($type === 'textarea')
        <textarea {{ new Illuminate\View\ComponentAttributeBag($control) }} class="field-control" rows="{{ $rows }}">{{ $value ?? $slot }}</textarea>
    @elseif ($type === 'select')
        <div class="relative">
            <select {{ new Illuminate\View\ComponentAttributeBag($control) }} class="field-control">
                @foreach ($options as $optionValue => $text)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $text }}</option>
                @endforeach
            </select>
            <svg class="pointer-events-none absolute right-4 top-1/2 size-3 -translate-y-1/2" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M2 4.5 6 8.5l4-4"/></svg>
        </div>
    @else
        <input type="{{ $type }}" {{ new Illuminate\View\ComponentAttributeBag($control) }} value="{{ $value }}" class="field-control">
    @endif

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-sm text-muted">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm font-medium">{{ $error }}</p>
    @endif
</div>
