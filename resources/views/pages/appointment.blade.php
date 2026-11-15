@php
    use App\Mail\AppointmentFormat;
    use App\Support\Locale;

    $tz = config('site.timezone');
    $keep = $moving ? ['verzet' => $moving->token] : [];
    $locale = app()->getLocale();
@endphp

{{-- Booking: type, then day, then time. Links only, so it works everywhere. --}}
<x-layouts.app :seo="$seo">
    <x-page-opening number="01" :label="__('leads.appointment.label')" :title="__('leads.appointment.heading')" :lead="__('leads.appointment.lead')" />

    <section>
        <div class="page pb-16 md:pb-28">
            @if ($moving)
                <p class="notice mb-10" role="status">{{ __('leads.appointment.rescheduling', ['when' => AppointmentFormat::when($moving, $locale)]) }}</p>
            @endif
            @error('slot')
                <p class="notice mb-10" role="alert">{{ $message }}</p>
            @enderror

            @if ($types->isEmpty())
                <div class="card max-w-2xl p-6 md:p-10">
                    <h2 class="t-h3">{{ __('leads.appointment.no_types_title') }}</h2>
                    <p class="mt-3 text-muted">{{ __('leads.appointment.no_slots_text') }}</p>
                    <p class="mt-8"><x-button :href="Locale::url('contact')">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
                </div>
            @else
                @if ($types->count() > 1 || ! $type)
                    <div data-reveal="group">
                        <p class="t-label rise text-muted">01 / {{ __('leads.appointment.choose_type') }}</p>
                        <ul class="choice-grid rise mt-4" data-delay="1">
                            @foreach ($types as $t)
                                <li>
                                    <a href="{{ Locale::route('appointment.pick', ['type' => $t->id] + $keep) }}" class="choice choice-link" @if ($type?->id === $t->id) aria-current="true" @endif>
                                        <span class="choice-body">
                                            <span class="choice-mark" aria-hidden="true"></span>
                                            <span class="choice-title">{{ loc($t->name) }}</span>
                                            <span class="choice-meta t-label text-muted">{{ __('leads.appointment.minutes', ['count' => $t->duration_minutes]) }}</span>
                                            @if ($place = loc($t->location) ?? ($t->is_online ? __('leads.appointment.online') : null))
                                                <span class="choice-text text-sm text-muted">{{ $place }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($type)
                    <div class="mt-14" id="times">
                        @if (empty($days))
                            <div class="card max-w-2xl p-6 md:p-10">
                                <h2 class="t-h3">{{ __('leads.appointment.no_slots_title') }}</h2>
                                <p class="mt-3 text-muted">{{ __('leads.appointment.no_slots_text') }}</p>
                                <p class="mt-8"><x-button :href="Locale::url('contact')">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
                            </div>
                        @else
                            <h2 class="t-label text-muted">{{ $types->count() > 1 ? '02' : '01' }} / {{ __('leads.appointment.choose_day') }}</h2>
                            <ul class="day-strip mt-4">
                                @foreach ($days as $day => $slots)
                                    @php($d = \Carbon\CarbonImmutable::parse($day, $tz)->locale($locale))
                                    <li>
                                        <a href="{{ (! empty($single) ? Locale::url('appointment') : Locale::route('appointment.pick', ['type' => $type->id])).'?'.http_build_query(['dag' => $day] + $keep) }}#times" class="day" @if ($day === $date) aria-current="date" @endif>
                                            <span class="t-label">{{ $d->translatedFormat('D') }}</span>
                                            <span class="day-num">{{ $d->format('j') }}</span>
                                            <span class="t-label text-muted">{{ $d->translatedFormat('M') }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <h2 class="t-label mt-12 text-muted">{{ $types->count() > 1 ? '03' : '02' }} / {{ __('leads.appointment.times_on', ['date' => \Carbon\CarbonImmutable::parse($date, $tz)->locale($locale)->translatedFormat('l j F')]) }}</h2>
                            <ul class="time-grid mt-4">
                                @foreach ($days[$date] as $slot)
                                    <li><a href="{{ Locale::route('appointment.details', ['type' => $type->id, 'slot' => $slot->format('Y-m-d\TH:i')] + $keep) }}" class="time">{{ $slot->timezone($tz)->format('H:i') }}</a></li>
                                @endforeach
                            </ul>
                            <p class="mt-6 text-sm text-muted">{{ __('leads.appointment.timezone') }}</p>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </section>
</x-layouts.app>
