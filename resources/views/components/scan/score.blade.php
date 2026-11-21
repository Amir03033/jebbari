@props(['label', 'score', 'large' => false])

@php($band = \App\Scan\Report::band($score))

{{-- One score: the number at display size, a hairline bar beneath it, the band in words. --}}
<div {{ $attributes->class(['scan-score', 'scan-score-lg' => $large]) }}>
    <p class="t-label text-muted">{{ $label }}</p>
    @if ($score === null)
        <p class="scan-score-num text-muted">–<span class="sr-only">{{ __('scan.report.not_measured') }}</span></p>
    @else
        <p class="scan-score-num">{{ $score }}<span class="sr-only"> {{ __('scan.report.out_of') }}</span></p>
    @endif
    <span class="scan-bar" data-step="{{ \App\Scan\Report::step($score) }}" aria-hidden="true"><span></span></span>
    <x-scan.state :status="$band" class="mt-3" />
</div>
