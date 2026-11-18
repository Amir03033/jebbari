@props(['check', 'category' => null])

@php
    use App\Scan\Report;

    $work = Report::needsWork($check);
@endphp

{{-- One check: status, name and finding; for anything to fix also why it matters and the advice. --}}
<li class="scan-check" data-reveal="group">
    <span class="rule" aria-hidden="true"></span>
    <div class="rise">
        <x-scan.state :status="$check['status']" />
        @if ($category)
            <p class="t-label mt-2 text-muted">{{ $category }}</p>
        @endif
    </div>
    <div class="rise" data-delay="1">
        <h3 class="t-h3">{{ Report::title($check) }}</h3>
        <p class="mt-2 text-muted">{{ Report::found($check) }}</p>
        @if ($work)
            <dl class="scan-advice mt-5">
                <div>
                    <dt class="t-label text-muted">{{ __('scan.report.why') }}</dt>
                    <dd class="mt-1">{{ Report::why($check) }}</dd>
                </div>
                <div>
                    <dt class="t-label text-muted">{{ __('scan.report.advice') }}</dt>
                    <dd class="mt-1">{{ Report::advice($check) }}</dd>
                </div>
            </dl>
        @endif
    </div>
</li>
