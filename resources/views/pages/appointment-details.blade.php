@php
    use App\Content\Site;
    use App\Support\Locale;

    $tz = config('site.timezone');
    $locale = app()->getLocale();
    $local = $start->timezone($tz)->locale($locale);
    $keep = $moving ? ['verzet' => $moving->token] : [];
    $place = loc($type->location) ?? ($type->is_online ? __('leads.appointment.online') : null);
@endphp

<x-layouts.app :seo="$seo">
    <x-page-opening number="02" :label="__('leads.appointment.label')" :title="$local->translatedFormat('l j F').', '.$local->format('H:i')" />

    <section>
        <div class="page pb-16 md:pb-28">
            <div class="grid-12 gap-y-10">
                <div class="col-span-12 lg:col-span-4">
                    <dl class="summary">
                        <div><dt class="t-label text-muted">{{ __('leads.appointment.what') }}</dt><dd>{{ loc($type->name) }} · {{ __('leads.appointment.minutes', ['count' => $type->duration_minutes]) }}</dd></div>
                        <div><dt class="t-label text-muted">{{ __('leads.appointment.date') }}</dt><dd>{{ $local->translatedFormat('l j F Y') }}</dd></div>
                        <div><dt class="t-label text-muted">{{ __('leads.appointment.time') }}</dt><dd>{{ $local->format('H:i') }} – {{ $local->addMinutes($type->duration_minutes)->format('H:i') }}</dd></div>
                        @if ($place)
                            <div><dt class="t-label text-muted">{{ __('leads.appointment.where') }}</dt><dd>{{ $place }}</dd></div>
                        @endif
                    </dl>
                    <p class="mt-6"><x-link :href="Locale::route('appointment.pick', ['type' => $type->id, 'dag' => $local->toDateString()] + $keep)">← {{ __('leads.appointment.change') }}</x-link></p>
                </div>

                <div id="book" class="card col-span-12 scroll-mt-28 p-6 md:p-10 lg:col-span-8">
                    <form method="post" action="{{ Locale::route('appointment.book', ['type' => $type->id, 'slot' => $slot] + $keep) }}#book" class="grid gap-6" novalidate>
                        @csrf
                        @if ($moving)
                            <input type="hidden" name="verzet" value="{{ $moving->token }}">
                            <p class="text-muted">{{ __('leads.appointment.rescheduling', ['when' => \App\Mail\AppointmentFormat::when($moving, $locale)]) }}</p>
                        @else
                            @if ($errors->any())
                                <p role="alert" class="rounded-ui border border-fg p-4 text-sm font-medium">{{ __('leads.form.fix_errors') }}</p>
                            @endif
                            <h2 class="t-h3">{{ __('leads.appointment.details') }}</h2>
                            <x-field name="name" :label="__('leads.form.name')" required autocomplete="name" maxlength="120" :value="old('name', $lead?->name)" :error="$errors->first('name')" />
                            <x-field name="email" type="email" :label="__('leads.form.email')" required autocomplete="email" maxlength="190" :value="old('email', $lead?->email)" :error="$errors->first('email')" />
                            <x-field name="phone" type="tel" :label="__('leads.form.phone')" autocomplete="tel" maxlength="40" :value="old('phone', $lead?->phone)" :error="$errors->first('phone')" />
                            <x-field name="company" :label="__('leads.form.company')" autocomplete="organization" maxlength="160" :value="old('company', $lead?->company)" :error="$errors->first('company')" />
                            <x-field name="message" type="textarea" :label="__('leads.appointment.message')" maxlength="2000" :rows="3" :value="old('message')" :error="$errors->first('message')" />
                            <x-scan.checkbox name="privacy" :label="__('leads.form.privacy')" required :error="$errors->first('privacy')" :checked="(bool) old('privacy')" />
                            <x-leads.guard />
                        @endif
                        <div><x-button type="submit">{{ __('leads.appointment.confirm') }}<span class="arrow" aria-hidden="true">→</span></x-button></div>
                        @if (! $moving && ($privacy = Site::pageUrl('privacy')))
                            <p class="text-sm text-muted"><x-link :href="$privacy">{{ __('leads.form.privacy_link') }}</x-link></p>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
