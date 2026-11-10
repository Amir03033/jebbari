@php
    use App\Support\Locale;

    $rows = [
        ['key' => 'site', 'label' => __('site.live.site'), 'url' => url('/up')],
        ['key' => 'pages', 'label' => __('site.live.pages'), 'url' => Locale::url('home')],
        ['key' => 'cms', 'label' => __('site.live.cms'), 'url' => route('filament.admin.auth.login')],
    ];
@endphp

{{--
    A live status panel in the spirit of a monitoring dashboard. Nothing here is
    made up: motion.js really requests these three addresses from the visitor's
    browser every 20 seconds while the panel is visible and shows what came back.
--}}
<section {{ $attributes->class('live') }} data-live data-ok="{{ __('site.live.all_ok') }}" data-bad="{{ __('site.live.degraded') }}" data-state="measuring" aria-label="{{ __('site.live.title') }}">
    <div class="live-head">
        <p class="t-label text-muted">{{ __('site.live.title') }}</p>
        <p class="live-state t-label" role="status" aria-live="polite"><span class="live-pulse" aria-hidden="true"></span><span data-live-state>{{ __('site.live.measuring') }}</span></p>
    </div>

    <ul class="live-rows">
        @foreach ($rows as $row)
            <li class="live-row" data-ping="{{ $row['url'] }}">
                <span class="live-name">{{ $row['label'] }}</span>
                <span class="live-bars" aria-hidden="true">@for ($i = 0; $i < 14; $i++)<span class="live-bar"></span>@endfor</span>
                <span class="live-ms t-label tabular-nums"><span data-ping-ms>·</span> {{ __('site.live.ms') }}</span>
            </li>
        @endforeach
    </ul>

    <p class="live-note t-label text-muted">{{ __('site.live.note') }}</p>
</section>
