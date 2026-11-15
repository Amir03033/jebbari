@php
    use App\Models\Scan;
    use App\Scan\Report;
    use App\Support\Locale;

    $locale = app()->getLocale();
    $stuck = $scan->isStuck();
    $waiting = ! $scan->isFinished() && ! $stuck;
    $failed = $scan->status === Scan::FAILED || $stuck;
    $alternates = collect(Locale::all())->mapWithKeys(fn ($l) => [$l => Locale::route('scan.show', ['scan' => $scan->token], $l)])->all();
@endphp

<x-layouts.app :title="$title" :noindex="true" :alternates="$alternates">
    @if ($waiting)
        {{-- Without JavaScript the page refreshes itself; with it, scan.js polls the status and reloads once. --}}
        @push('head')
            <noscript><meta http-equiv="refresh" content="4"></noscript>
        @endpush

        @php
            $checkNames = collect(__('scan.check'))->pluck('title')->values()->all();
        @endphp

        <section class="opening error-stage">
            <span class="opening-glow" aria-hidden="true"></span>
            <div class="page-wide relative" data-reveal="group">
                <p class="t-label rise text-muted" data-delay="1">01 / {{ __('scan.page.label') }} · {{ $scan->domain }}</p>
                <h1 class="t-display mt-6"><x-lines :lines="[__('scan.status.title')]" /></h1>

                <div class="scan-live rise mt-10 md:mt-16" data-delay="4" data-scan-live
                    data-status="{{ $scan->status }}"
                    data-status-url="{{ Locale::route('scan.status', ['scan' => $scan->token]) }}"
                    data-started="{{ ($scan->started_at ?? $scan->created_at)->getTimestamp() }}"
                    data-checks="{{ json_encode($checkNames) }}">
                    <div class="scan-sheet" aria-hidden="true">
                        <span class="scan-sheet-bar"></span>
                        <span class="scan-sheet-title"></span>
                        <span class="scan-sheet-line"></span>
                        <span class="scan-sheet-line is-short"></span>
                        <span class="scan-sheet-media"></span>
                        <span class="scan-sheet-line"></span>
                        <span class="scan-sheet-line is-short"></span>
                        <span class="scan-sheet-line"></span>
                        <span class="scan-sheet-line is-short"></span>
                        <span class="scan-sheet-line"></span>
                        <span class="scan-beam"></span>
                    </div>

                    <div>
                        <p class="t-statement text-muted" role="status" data-scan-text data-pending="{{ __('scan.status.pending_text') }}" data-running="{{ __('scan.status.running_text') }}">{{ __('scan.status.'.$scan->status.'_text') }}</p>
                        <ol class="scan-phases mt-8">
                            @foreach (__('scan.status.steps') as $step)
                                <li class="scan-phase" data-scan-phase="{{ $loop->index }}">
                                    <span class="t-label text-muted">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="scan-phase-name">{{ $step }}</span>
                                    <span class="scan-phase-mark" aria-hidden="true"></span>
                                    <span class="scan-phase-fill" aria-hidden="true"><span></span></span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="scan-now t-label mt-6 text-muted" aria-hidden="true"><span>{{ __('scan.status.now') }}</span> <span class="scan-now-name" data-scan-now>{{ $checkNames[0] ?? '' }}</span></p>
                        <p class="mt-6 text-sm text-muted">{{ __('scan.status.refresh') }}</p>
                    </div>
                </div>
            </div>
        </section>
    @elseif ($failed)
        <section class="opening error-stage">
            <span class="opening-glow" aria-hidden="true"></span>
            <div class="page-wide relative" data-reveal="group">
                <p class="t-label rise text-muted" data-delay="1">01 / {{ __('scan.page.label') }} · {{ $scan->domain }}</p>
                <h1 class="t-display mt-6"><x-lines :lines="[__('scan.status.failed_title')]" /></h1>
                <div class="grid-12 mt-10 md:mt-16">
                    <p class="t-statement rise col-span-12 text-muted md:col-span-6 md:col-start-7" data-delay="4">{{ __('scan.error.'.($stuck ? 'stuck' : ($scan->error ?: 'internal'))) }}</p>
                    <p class="rise col-span-12 mt-8 md:col-span-6 md:col-start-7" data-delay="5"><x-button :href="Locale::url('scan')">{{ __('scan.status.retry') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
                </div>
            </div>
        </section>
    @else
        @php
            $priorities = Report::priorities($scan);
            $speedEstimated = collect($scan->results['speed'] ?? [])->firstWhere('key', 'lcp')['status'] ?? null;
            $contactUrl = Locale::url('contact').'?'.http_build_query(['scan' => $scan->url]).'#contact-form';
        @endphp

        <x-page-opening number="01" :label="__('scan.report.label')" :title="__('scan.report.title', ['domain' => $scan->domain])"
            :meta="__('scan.report.checked', ['date' => $scan->finished_at?->timezone(config('site.timezone'))->translatedFormat('j F Y, H:i')])" :lead="$scan->url" />

        <section aria-labelledby="scores">
            <x-section-label number="02" :label="__('scan.report.scores_label')" plain />
            <h2 id="scores" class="sr-only">{{ __('scan.report.scores_label') }}</h2>
            <div class="page section-body-bottom">
                <div class="scan-scores" data-reveal="group">
                    <x-scan.score class="rise" :label="__('scan.report.overall')" :score="$scan->overall()" large />
                    @foreach (Scan::CATEGORIES as $category)
                        <x-scan.score class="rise" data-delay="{{ $loop->iteration }}" :label="__('scan.category.'.$category)" :score="$scan->scores[$category] ?? null" />
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <x-section-label number="03" :label="__('scan.report.priorities_label')" />
            <div class="page section-body">
                @if ($priorities === [])
                    <p class="t-statement text-muted" data-reveal="up">{{ __('scan.report.priorities_empty') }}</p>
                @else
                    <ol class="scan-checks">
                        @foreach ($priorities as $check)
                            <x-scan.check-row :check="$check" :category="__('scan.category.'.$check['category'])" />
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>

        @foreach (Scan::CATEGORIES as $category)
            <section>
                <x-section-label :number="str_pad($loop->iteration + 3, 2, '0', STR_PAD_LEFT)" :label="__('scan.category.'.$category)" :meta="isset($scan->scores[$category]) ? $scan->scores[$category].'/100' : __('scan.report.not_measured')" />
                <div class="page section-body">
                    @if ($category === 'speed' && $speedEstimated === 'skipped')
                        <p class="mb-8 text-sm text-muted" data-reveal="up">{{ __('scan.report.speed_estimate') }}</p>
                    @endif
                    <ul class="scan-checks">
                        @foreach ($scan->results[$category] ?? [] as $check)
                            <x-scan.check-row :check="$check" />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endforeach

        <div class="page pb-16" data-reveal="up">
            <p class="text-sm text-muted md:max-w-[62ch]">{{ __('scan.report.disclaimer') }}</p>
        </div>

        <x-cta-panel :lines="[__('scan.report.cta_title')]" :label="__('scan.name')" id="scan-cta">
            <p class="t-statement text-muted md:max-w-[44ch]">{{ __('scan.report.cta_text') }}</p>
            <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4">
                <x-button :href="$contactUrl">{{ __('scan.report.cta_button') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                <x-link :href="Locale::url('scan')">{{ __('scan.report.again') }}</x-link>
            </div>
        </x-cta-panel>
    @endif
</x-layouts.app>
