@props(['step', 'total', 'labels' => []])

{{-- Where you are in the wizard: numbered steps and a hairline that fills. --}}
<nav {{ $attributes->class('progress') }} aria-label="{{ __('leads.request.step', ['step' => $step, 'total' => $total]) }}">
    <p class="t-label text-muted">{{ __('leads.request.step', ['step' => $step, 'total' => $total]) }}</p>
    <ol class="progress-steps">
        @foreach ($labels as $i => $label)
            <li @class(['is-done' => $i + 1 < $step, 'is-current' => $i + 1 === $step]) @if ($i + 1 === $step) aria-current="step" @endif>
                <span class="t-label">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="progress-name">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
    <span class="progress-bar" data-step="{{ $step }}" aria-hidden="true"><span></span></span>
</nav>
