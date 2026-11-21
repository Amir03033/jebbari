@php
    use App\Content\Site;
    use App\Leads\PriceCalculator;
    use App\Support\Locale;

    $groups = ['project' => 'radio', 'size' => 'radio', 'extra' => 'checkbox', 'deadline' => 'radio'];
    $available = $items->has('project');
    $number = 0;
    $json = json_encode(['items' => $data, 'names' => $names], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
@endphp

{{-- The price calculator: choose, see a range. Works without JavaScript; Alpine updates the range live. --}}
<x-layouts.app :seo="$seo">
    <x-page-opening number="01" :label="__('leads.price.label')" :title="__('leads.price.heading')" :lead="__('leads.price.lead')" />

    <section>
        <div class="page pb-16 md:pb-28">
            @if (! $available)
                <div class="card max-w-2xl p-6 md:p-10" data-reveal="up">
                    <h2 class="t-h3">{{ __('leads.price.unavailable_title') }}</h2>
                    <p class="mt-3 text-muted">{{ __('leads.price.unavailable_text') }}</p>
                    <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4">
                        <x-button :href="Locale::url('request')">{{ __('leads.cta.request') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                        <x-link :href="Locale::url('appointment')">{{ __('leads.cta.appointment') }}</x-link>
                    </div>
                </div>
            @else
                <form method="get" action="{{ Locale::url('price') }}" class="calc grid-12 gap-y-12" novalidate
                    x-data="priceCalculator" data-calc="{{ $json }}" data-locale="{{ app()->getLocale() }}">
                    <div class="col-span-12 grid gap-12 lg:col-span-7">
                        @foreach ($groups as $group => $input)
                            @continue(! $items->has($group))
                            @php($number++)
                            <fieldset class="calc-group" data-reveal="group">
                                <legend class="t-label rise text-muted"><span>{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span> / {{ __('leads.price.step_'.$group) }}</legend>
                                <div class="choice-grid rise mt-4" data-delay="1">
                                    @if ($group !== 'project' && $input === 'radio')
                                        <x-leads.choice :name="$group" value="" :title="__('leads.price.none')" :checked="empty($choices[$group])" x-model="{{ $group }}" />
                                    @endif
                                    @foreach ($items[$group] as $item)
                                        <x-leads.choice
                                            :name="$input === 'checkbox' ? 'extras[]' : $group"
                                            :value="$item->key"
                                            :type="$input"
                                            :checked="$input === 'checkbox' ? in_array($item->key, $choices['extras'] ?? [], true) : ($choices[$group] ?? null) === $item->key"
                                            :title="loc($item->name) ?? $item->key"
                                            :text="loc($item->description)"
                                            :meta="($group === 'project' ? '' : '+ ').PriceCalculator::range($item->price_min, $item->price_max)"
                                            x-model="{{ $input === 'checkbox' ? 'extras' : $group }}" />
                                    @endforeach
                                </div>
                                @if ($group === 'project')
                                    @error('project')<p class="mt-3 text-sm font-medium" role="alert">{{ $message }}</p>@enderror
                                @endif
                            </fieldset>
                        @endforeach
                        <p class="calc-nojs"><x-button type="submit" variant="secondary">{{ __('leads.price.calculate') }}</x-button></p>
                    </div>

                    <aside id="estimate" class="col-span-12 lg:col-span-5" aria-live="polite">
                        <div class="calc-result card p-6 md:p-8">
                            <p class="t-label text-muted">{{ __('leads.price.estimate') }}</p>
                            <p class="calc-range mt-4" x-text="range">{{ $result ? PriceCalculator::range($result['min'], $result['max']) : '–' }}</p>
                            <p class="mt-3 text-sm text-muted" x-bind:hidden="filled" @if ($result) hidden @endif>{{ __('leads.price.choose_project') }}</p>

                            <div class="mt-8" x-bind:hidden="empty" @unless ($result) hidden @endunless>
                                <p class="t-label text-muted">{{ __('leads.price.included') }}</p>
                                <ul class="calc-lines mt-3">
                                    <template x-for="line in lines" x-bind:key="line.key">
                                        <li><span x-text="line.name"></span><span class="text-muted" x-text="line.price"></span></li>
                                    </template>
                                    @foreach ($result['lines'] ?? [] as $line)
                                        <li class="calc-server-line"><span>{{ $names[$line['key']] ?? $line['key'] }}</span><span class="text-muted">{{ PriceCalculator::range($line['min'], $line['max']) }}</span></li>
                                    @endforeach
                                </ul>
                            </div>

                            <p class="mt-6 text-sm text-muted">{{ __('leads.price.disclaimer') }}</p>

                            <div class="mt-8 grid gap-4">
                                @csrf
                                <x-button type="submit" formmethod="post" formaction="{{ Locale::route('price.save') }}" name="next" value="request">{{ __('leads.cta.request') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                                <x-button type="submit" formmethod="post" formaction="{{ Locale::route('price.save') }}" name="next" value="appointment" variant="secondary">{{ __('leads.cta.appointment') }}</x-button>
                            </div>

                            <div class="calc-email mt-10">
                                @if (session('estimate_sent'))
                                    <p class="text-sm font-medium" role="status">{{ __('leads.price.email_sent') }}</p>
                                @else
                                    <p class="t-label text-muted">{{ __('leads.price.email_label') }}</p>
                                    <div class="mt-3 grid gap-4">
                                        <label for="f-estimate-email" class="sr-only">{{ __('leads.form.email') }}</label>
                                        <input id="f-estimate-email" type="email" name="email" class="field-control" autocomplete="email" maxlength="190" placeholder="{{ __('leads.form.email') }}" value="{{ old('email') }}" @error('email', 'estimate') aria-invalid="true" aria-describedby="f-estimate-email-error" @enderror>
                                        @error('email', 'estimate')<p id="f-estimate-email-error" class="text-sm font-medium">{{ $message }}</p>@enderror
                                        <x-scan.checkbox name="privacy" :label="__('leads.price.email_privacy')" :error="$errors->estimate->first('privacy')" />
                                        <x-leads.guard />
                                        <div><x-button type="submit" formmethod="post" formaction="{{ Locale::route('price.save') }}" name="next" value="email" variant="secondary">{{ __('leads.price.email_submit') }}</x-button></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </aside>
                </form>
            @endif
        </div>
    </section>
</x-layouts.app>
