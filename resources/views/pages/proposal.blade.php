@php
    use App\Leads\PriceCalculator;

    $locale = app()->getLocale();
    $tz = config('site.timezone');
    $date = fn ($d) => $d->copy()->timezone($tz)->locale($locale)->translatedFormat('j F Y');
    $state = $proposal->state();
    $lines = $proposal->priceLines();
    $scope = collect($proposal->scope ?? [])->filter(fn ($i) => filled($i['title'] ?? null))->values();
    $timeline = collect($proposal->timeline ?? [])->filter(fn ($i) => filled($i['phase'] ?? null))->values();
    // Section numbers for the parts that are present (computed once: component props can be evaluated twice).
    $parts = array_keys(array_filter([
        'intro' => filled($proposal->intro),
        'scope' => $scope->isNotEmpty(),
        'timeline' => $timeline->isNotEmpty(),
        'prices' => (bool) $lines,
        'terms' => filled($proposal->terms),
        'decide' => true,
    ]));
    $no = fn (string $part) => str_pad((string) (array_search($part, $parts, true) + 1), 2, '0', STR_PAD_LEFT);
    $flash = session('proposal');
@endphp

{{--
    A personal proposal: what, when and for how much, then the client's
    decision. Private (token link, noindex). Prints cleanly ("Save as PDF").
--}}
<x-layouts.app :seo="$seo">
    <x-page-opening
        number="01"
        :label="__('proposal.label')"
        :title="$proposal->title"
        :lead="__('proposal.for', ['name' => $proposal->client_company ? $proposal->client_name.', '.$proposal->client_company : $proposal->client_name])"
    />

    <section>
        <div class="page pb-6">
            <div class="proposal-meta t-label text-muted">
                <span>{{ __('proposal.date', ['date' => $date($proposal->sent_at ?? $proposal->created_at)]) }}</span>
                @if ($proposal->valid_until)
                    <span>{{ __('proposal.valid', ['date' => $proposal->valid_until->locale($locale)->translatedFormat('j F Y')]) }}</span>
                @endif
                <span class="proposal-print"><x-button type="button" variant="secondary" data-print>{{ __('proposal.print') }}<span class="arrow" aria-hidden="true">↓</span></x-button></span>
            </div>
            @if ($flash)
                <p class="proposal-flash mt-8" role="status">{{ __('proposal.flash_'.$flash) }}</p>
            @endif
        </div>
    </section>

    @if (filled($proposal->intro))
        <section>
            <x-section-label :number="$no('intro')" :label="__('proposal.intro')" />
            <div class="page section-body">
                <div class="grid-12">
                    <div class="prose-jebbari col-span-12 md:col-span-8 md:col-start-5" data-reveal="up">
                        @foreach (preg_split('/\R{2,}/', trim($proposal->intro)) as $para)
                            <p>{!! nl2br(e($para)) !!}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($scope->isNotEmpty())
        <section>
            <x-section-label :number="$no('scope')" :label="__('proposal.scope')" :meta="'('.$scope->count().')'" />
            <div class="page section-body">
                <ol class="proposal-list">
                    @foreach ($scope as $item)
                        <li class="proposal-row" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                            <span class="rule" aria-hidden="true"></span>
                            <span class="t-label rise text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="t-h3 rise">{{ $item['title'] }}</h3>
                            @if (filled($item['description'] ?? null))
                                <p class="rise text-muted" data-delay="1">{{ $item['description'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($timeline->isNotEmpty())
        <section>
            <x-section-label :number="$no('timeline')" :label="__('proposal.timeline')" />
            <div class="page section-body">
                <ol class="proposal-list">
                    @foreach ($timeline as $item)
                        <li class="proposal-row" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
                            <span class="rule" aria-hidden="true"></span>
                            <span class="t-label rise text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="t-h3 rise">{{ $item['phase'] }}</h3>
                            @if (filled($item['duration'] ?? null))
                                <p class="rise text-muted" data-delay="1">{{ $item['duration'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($lines)
        <section>
            <x-section-label :number="$no('prices')" :label="__('proposal.prices')" />
            <div class="page section-body">
                <table class="proposal-prices" data-reveal="up">
                    <caption class="sr-only">{{ __('proposal.prices') }}</caption>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <th scope="row">{{ $line['description'] }}</th>
                                <td class="t-label text-muted">{{ __('proposal.period.'.$line['period']) }}</td>
                                <td class="tabular-nums">{{ PriceCalculator::money($line['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @foreach ($proposal->totals() as $period => $sum)
                            <tr>
                                <th scope="row">{{ __('proposal.total') }}</th>
                                <td class="t-label text-muted">{{ __('proposal.period.'.$period) }}</td>
                                <td class="tabular-nums">{{ PriceCalculator::money($sum) }}</td>
                            </tr>
                        @endforeach
                    </tfoot>
                </table>
            </div>
        </section>
    @endif

    @if (filled($proposal->terms))
        <section>
            <x-section-label :number="$no('terms')" :label="__('proposal.terms')" />
            <div class="page section-body">
                <div class="grid-12">
                    <div class="prose-jebbari col-span-12 text-muted md:col-span-8 md:col-start-5" data-reveal="up">
                        @foreach (preg_split('/\R{2,}/', trim($proposal->terms)) as $para)
                            <p>{!! nl2br(e($para)) !!}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section aria-labelledby="decide-title">
        <x-section-label :number="$no('decide')" :label="__('proposal.decide')" />
        <div class="page section-body">
            <div class="proposal-decide" data-reveal="up">
                @if ($state === 'accepted')
                    <h3 id="decide-title" class="t-h2">{{ __('proposal.accepted_title') }}</h3>
                    <p class="mt-4 max-w-[52ch] text-muted">{{ __('proposal.accepted_text', ['name' => $proposal->accepted_name, 'date' => $date($proposal->accepted_at)]) }}</p>
                @elseif ($state === 'declined')
                    <h3 id="decide-title" class="t-h2">{{ __('proposal.declined_title') }}</h3>
                    <p class="mt-4 max-w-[52ch] text-muted">{{ __('proposal.declined_text', ['date' => $date($proposal->declined_at)]) }}</p>
                @elseif ($state === 'expired')
                    <h3 id="decide-title" class="t-h2">{{ __('proposal.expired_title') }}</h3>
                    <p class="mt-4 max-w-[52ch] text-muted">{{ __('proposal.expired_text', ['date' => $proposal->valid_until->locale($locale)->translatedFormat('j F Y')]) }}</p>
                @else
                    <h3 id="decide-title" class="sr-only">{{ __('proposal.decide') }}</h3>
                    <form method="post" action="{{ route($locale.'.proposal.accept', ['proposal' => $proposal->token]) }}" class="grid gap-6" novalidate>
                        @csrf
                        <p class="max-w-[52ch] text-muted">{{ __('proposal.accept_intro') }}</p>
                        <x-field name="name" :label="__('proposal.name')" required autocomplete="name" maxlength="120" :value="old('name', $proposal->client_name)" :error="$errors->first('name')" class="max-w-[28rem]" />
                        <div class="flex flex-col gap-2">
                            <div class="check-row">
                                <input type="checkbox" id="f-agree" name="agree" value="1" class="check-control" @checked(old('agree')) required @if ($errors->has('agree')) aria-invalid="true" aria-describedby="f-agree-error" @endif>
                                <label for="f-agree" class="text-sm leading-relaxed">
                                    @if ($termsUrl)
                                        {!! __('proposal.agree_terms', ['terms' => '<a class="link" href="'.e($termsUrl).'" target="_blank" rel="noopener">'.e(__('proposal.terms_link')).'</a>']) !!}
                                    @else
                                        {{ __('proposal.agree') }}
                                    @endif
                                </label>
                            </div>
                            @if ($errors->has('agree'))
                                <p id="f-agree-error" class="text-sm font-medium">{{ $errors->first('agree') }}</p>
                            @endif
                        </div>
                        <div><x-button type="submit">{{ __('proposal.accept') }}<span class="arrow" aria-hidden="true">→</span></x-button></div>
                    </form>

                    <details class="cancel-box mt-10">
                        <summary class="link link-bare">{{ __('proposal.decline_open') }}</summary>
                        <form method="post" action="{{ route($locale.'.proposal.decline', ['proposal' => $proposal->token]) }}" class="mt-5 grid max-w-[36rem] gap-4">
                            @csrf
                            <x-field name="reason" type="textarea" :label="__('proposal.decline_reason')" maxlength="2000" :rows="3" :value="old('reason')" />
                            <div><x-button type="submit" variant="secondary">{{ __('proposal.decline') }}</x-button></div>
                        </form>
                    </details>
                @endif
            </div>

            @if ($email)
                <p class="mt-10 text-muted">{{ __('proposal.questions') }} <a class="link" href="mailto:{{ $email }}">{{ $email }}</a></p>
            @endif
        </div>
    </section>
</x-layouts.app>
