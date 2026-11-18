@php
    use App\Mail\AppointmentFormat;
    use App\Models\Appointment;
    use App\Support\Locale;

    $locale = app()->getLocale();
    $state = $appointment->status === Appointment::CANCELLED ? 'cancelled' : ($appointment->isUpcoming() ? 'upcoming' : 'past');
    $title = match (true) {
        (bool) session('booked') => __('leads.appointment.booked_title'),
        $state === 'cancelled' => __('leads.appointment.cancelled_title'),
        $state === 'past' => __('leads.appointment.past_title'),
        default => __('leads.appointment.manage_title'),
    };
@endphp

<x-layouts.app :seo="$seo">
    <x-page-opening number="03" :label="__('leads.appointment.label')" :title="$title" :lead="session('booked') ? __('leads.appointment.booked_text') : (session('cancelled') ? __('leads.appointment.cancelled_flash') : null)" />

    <section>
        <div class="page pb-16 md:pb-28">
            <div class="grid-12 gap-y-10">
                <dl class="summary col-span-12 lg:col-span-6" @if ($state !== 'upcoming') data-muted @endif>
                    @foreach (AppointmentFormat::rows($appointment, $locale) as $label => $value)
                        <div><dt class="t-label text-muted">{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>

                @if ($state === 'upcoming')
                    <div class="col-span-12 grid content-start gap-6 lg:col-span-5 lg:col-start-8">
                        <p><x-button :href="Locale::route('appointment.ics', ['appointment' => $appointment->token])" variant="secondary">{{ __('leads.appointment.add_calendar') }}</x-button></p>
                        <p><x-link :href="Locale::route('appointment.pick', ['type' => $appointment->appointment_type_id, 'verzet' => $appointment->token])">{{ __('leads.appointment.reschedule') }}</x-link></p>
                        <details class="cancel-box">
                            <summary class="link link-bare">{{ __('leads.appointment.cancel') }}</summary>
                            <form method="post" action="{{ Locale::route('appointment.cancel', ['appointment' => $appointment->token]) }}" class="mt-4 grid gap-4">
                                @csrf
                                <p class="text-sm text-muted">{{ __('leads.appointment.cancel_confirm') }}</p>
                                <div><x-button type="submit" variant="secondary">{{ __('leads.appointment.cancel_button') }}</x-button></div>
                            </form>
                        </details>
                    </div>
                @else
                    <div class="col-span-12 lg:col-span-5 lg:col-start-8">
                        <x-button :href="Locale::url('appointment')">{{ __('leads.mail.book_again') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
