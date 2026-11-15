@php
    use App\Content\Site;
    use App\Http\Requests\StoreInquiryRequest;
    use App\Models\Lead;
    use App\Support\Locale;

    $total = Lead::WIZARD_STEPS;
    $budgets = collect(['' => __('leads.request.budget_choose'), 'lt-2.5k' => '< €2.5k', '2.5-7.5k' => '€2.5–7.5k', '7.5-15k' => '€7.5–15k', '15k+' => '€15k+', 'unsure' => __('leads.request.budget_unsure')]);
    $deadlines = ['' => __('leads.request.deadline_choose')] + __('leads.request.deadlines');
    $examples = old('examples', $w['examples'] ?? []);
    $project = old('project', $w['project'] ?? null);
@endphp

{{-- The request wizard: one short step at a time, saved as you go. --}}
<x-layouts.app :seo="$seo">
    <x-page-opening number="01" :label="__('leads.request.label')" :title="__('leads.request.heading')" />

    <section>
        <div class="page pb-16 md:pb-28">
            <div class="grid-12 gap-y-10">
                <div class="col-span-12 lg:col-span-4">
                    <x-leads.progress :step="$step" :total="$total" :labels="__('leads.request.steps')" />
                    @if ($calculator && $estimate)
                        <div class="mt-10">
                            <p class="t-label text-muted">{{ __('leads.request.from_calculator') }}</p>
                            <p class="mt-2">{{ collect([$calculator['project'] ?? null, $calculator['size'] ?? null, ...($calculator['extras'] ?? []), $calculator['deadline'] ?? null])->filter()->map(fn ($k) => $names[$k] ?? null)->filter()->implode(' · ') }}</p>
                            <p class="mt-1 text-muted">{{ $estimate }}</p>
                        </div>
                    @endif
                </div>

                <div id="request-form" class="card col-span-12 scroll-mt-28 p-6 md:p-10 lg:col-span-8">
                    <form method="post" action="{{ Locale::route('request.save', ['step' => $step]) }}#request-form" class="grid gap-6" novalidate>
                        @csrf
                        @if ($errors->any())
                            <p role="alert" class="rounded-ui border border-fg p-4 text-sm font-medium">{{ __('leads.form.fix_errors') }}</p>
                        @endif

                        <h2 class="t-h3">{{ __('leads.request.steps')[$step - 1] }}</h2>

                        @switch($step)
                            @case(1)
                                @if ($projects->isNotEmpty())
                                    <fieldset>
                                        <legend class="t-label">{{ __('leads.request.project') }} <span class="text-muted">({{ __('site.field.optional') }})</span></legend>
                                        <div class="choice-grid mt-3">
                                            @foreach ($projects as $item)
                                                <x-leads.choice name="project" :value="$item->key" :checked="$project === $item->key" :title="loc($item->name) ?? $item->key" />
                                            @endforeach
                                            <x-leads.choice name="project" value="other" :checked="$project === 'other'" :title="__('leads.request.project_other')" />
                                        </div>
                                        @error('project')<p class="mt-2 text-sm font-medium">{{ $message }}</p>@enderror
                                    </fieldset>
                                @endif
                                <x-field name="summary" type="textarea" :label="__('leads.request.summary')" :hint="__('leads.request.summary_hint')" required maxlength="3000" :rows="5" :value="old('summary', $w['summary'] ?? '')" :error="$errors->first('summary')" />
                                @break

                            @case(2)
                                <x-field name="goal" type="textarea" :label="__('leads.request.goal')" :hint="__('leads.request.goal_hint')" maxlength="2000" :rows="4" :value="old('goal', $w['goal'] ?? '')" :error="$errors->first('goal')" />
                                <x-field name="audience" type="textarea" :label="__('leads.request.audience')" maxlength="2000" :rows="3" :value="old('audience', $w['audience'] ?? '')" :error="$errors->first('audience')" />
                                @break

                            @case(3)
                                <x-field name="budget" type="select" :label="__('leads.request.budget')" :options="$budgets" :value="old('budget', $w['budget'] ?? '')" :error="$errors->first('budget')" />
                                <x-field name="deadline" type="select" :label="__('leads.request.deadline')" :options="$deadlines" :value="old('deadline', $w['deadline'] ?? '')" :error="$errors->first('deadline')" />
                                @break

                            @case(4)
                                <x-field name="features" type="textarea" :label="__('leads.request.features')" :hint="__('leads.request.features_hint')" maxlength="3000" :rows="4" :value="old('features', $w['features'] ?? '')" :error="$errors->first('features')" />
                                <fieldset class="grid gap-3">
                                    <legend class="t-label">{{ __('leads.request.examples') }} <span class="text-muted">({{ __('site.field.optional') }})</span></legend>
                                    <p class="text-sm text-muted">{{ __('leads.request.examples_hint') }}</p>
                                    @foreach (range(0, 2) as $i)
                                        <label for="f-example-{{ $i }}" class="sr-only">{{ __('leads.request.example', ['n' => $i + 1]) }}</label>
                                        <input id="f-example-{{ $i }}" type="url" name="examples[]" class="field-control" inputmode="url" maxlength="500" placeholder="https://" value="{{ $examples[$i] ?? '' }}" @error('examples.'.$i) aria-invalid="true" @enderror>
                                        @error('examples.'.$i)<p class="text-sm font-medium">{{ $message }}</p>@enderror
                                    @endforeach
                                </fieldset>
                                @break

                            @case(5)
                                <x-field name="name" :label="__('leads.form.name')" required autocomplete="name" maxlength="120" :value="old('name', $lead?->name)" :error="$errors->first('name')" />
                                <x-field name="email" type="email" :label="__('leads.form.email')" required autocomplete="email" maxlength="190" :value="old('email', $lead?->email)" :error="$errors->first('email')" />
                                <x-field name="phone" type="tel" :label="__('leads.form.phone')" autocomplete="tel" maxlength="40" :value="old('phone', $lead?->phone)" :error="$errors->first('phone')" />
                                <x-field name="company" :label="__('leads.form.company')" autocomplete="organization" maxlength="160" :value="old('company', $lead?->company)" :error="$errors->first('company')" />
                                <x-scan.checkbox name="privacy" :label="__('leads.form.privacy')" required :error="$errors->first('privacy')" :checked="(bool) old('privacy')" />
                                <x-leads.guard />
                                @if ($privacy = Site::pageUrl('privacy'))
                                    <p class="text-sm text-muted"><x-link :href="$privacy">{{ __('leads.form.privacy_link') }}</x-link></p>
                                @endif
                                @break
                        @endswitch

                        <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                            @if ($step > 1)
                                <x-link :href="Locale::route('request.step', ['step' => $step - 1])">← {{ __('leads.request.back') }}</x-link>
                            @else
                                <span></span>
                            @endif
                            <x-button type="submit">{{ $step === $total ? __('leads.request.send') : __('leads.request.next') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                        </div>

                        <p class="text-sm text-muted">{{ __('leads.request.saved') }}
                            @if ($lead)
                                {{ __('leads.request.resume_hint') }} <x-link :href="Locale::route('request.resume', ['lead' => $lead->token])">{{ __('leads.request.resume_link') }}</x-link>
                            @endif
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
