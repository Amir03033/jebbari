<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeadPages;
use App\Leads\Booking;
use App\Leads\Ics;
use App\Leads\LeadSession;
use App\Leads\SlotFinder;
use App\Leads\SlotTaken;
use App\Leads\SpamGuard;
use App\Mail\AppointmentFormat;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Support\Locale;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Booking an introduction: type, day and time, details, confirmation. Plain
 * links and forms (no JavaScript needed). The manage page (token) lets the
 * visitor download the .ics, reschedule or cancel.
 */
class AppointmentController extends Controller
{
    use LeadPages;

    public function index(Request $request, SlotFinder $slots): View
    {
        $types = AppointmentType::query()->live()->get();

        // One kind of meeting: show its days and times right here.
        if ($types->count() === 1) {
            return $this->pick($request, $slots, $types->first()->id);
        }

        return view('pages.appointment', ['seo' => $this->seo('appointment', 'appointment'), 'types' => $types, 'type' => null, 'moving' => $this->moving($request)]);
    }

    public function pick(Request $request, SlotFinder $slots, int $type): View
    {
        $type = AppointmentType::query()->live()->findOrFail($type);
        $days = $slots->days($type);
        $date = array_key_exists((string) $request->query('dag'), $days) ? $request->query('dag') : array_key_first($days);

        return view('pages.appointment', [
            'seo' => $this->seo('appointment', 'appointment'),
            'types' => AppointmentType::query()->live()->get(),
            'single' => AppointmentType::query()->live()->count() === 1,
            'type' => $type,
            'days' => $days,
            'date' => $date,
            'moving' => $this->moving($request),
        ]);
    }

    public function details(Request $request, SlotFinder $slots, LeadSession $leads, int $type, string $slot): View|RedirectResponse
    {
        $type = AppointmentType::query()->live()->findOrFail($type);
        $start = $this->parseSlot($slot);

        if (! $start || ! $slots->isFree($type, $start)) {
            return redirect(Locale::route('appointment.pick', ['type' => $type->id] + $this->keep($request)))->withErrors(['slot' => __('leads.appointment.slot_taken')]);
        }

        $moving = $this->moving($request);

        return view('pages.appointment-details', [
            'seo' => $this->seo('appointment', 'appointment', false),
            'type' => $type,
            'start' => $start,
            'slot' => $slot,
            'lead' => $moving?->lead ?? $leads->current($request),
            'moving' => $moving,
        ]);
    }

    public function book(Request $request, Booking $booking, LeadSession $leads, SpamGuard $guard, int $type, string $slot): RedirectResponse
    {
        $type = AppointmentType::query()->live()->findOrFail($type);
        $start = $this->parseSlot($slot);
        $moving = $this->moving($request);
        $pick = redirect(Locale::route('appointment.pick', ['type' => $type->id] + $this->keep($request)));

        if (! $start) {
            return $pick->withErrors(['slot' => __('leads.appointment.slot_taken')]);
        }

        if (! $moving) {
            if (SpamGuard::isBot($request)) {
                return redirect(Locale::url('appointment'));
            }

            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190'],
                'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-]{6,40}$/'],
                'company' => ['nullable', 'string', 'max:160'],
                'message' => ['nullable', 'string', 'max:2000'],
                'privacy' => ['accepted'],
                '_t' => ['required', 'string'],
            ], ['privacy.accepted' => __('leads.form.privacy_required')], [
                'name' => __('leads.form.name'), 'email' => __('leads.form.email'), 'phone' => __('leads.form.phone'), 'message' => __('leads.appointment.message'),
            ]);
            $guard->check($request, 'name');

            $lead = $leads->currentOrCreate($request, 'appointment');
            $lead->fill([
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => trim((string) ($data['phone'] ?? '')) ?: $lead->phone,
                'company' => trim((string) ($data['company'] ?? '')) ?: $lead->company,
                'contact_consent' => true,
            ])->save();
        } else {
            $lead = $moving->lead;
        }

        try {
            $appointment = $booking->book($lead, $type, $start, $moving ? $moving->message : (trim((string) ($data['message'] ?? '')) ?: null), $moving);
        } catch (SlotTaken) {
            return $pick->withErrors(['slot' => __('leads.appointment.slot_taken')]);
        }

        $leads->remember($request, $lead);

        return redirect(Locale::route('appointment.manage', ['appointment' => $appointment->token]))->with('booked', true);
    }

    public function manage(Appointment $appointment): Response
    {
        $appointment->load('lead', 'type');

        return response(view('pages.appointment-manage', ['seo' => $this->seo('appointment', 'appointment', false), 'appointment' => $appointment]))
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function cancel(Appointment $appointment, Booking $booking): RedirectResponse
    {
        if ($appointment->isUpcoming()) {
            $booking->cancel($appointment->load('lead', 'type'));
        }

        return redirect(Locale::route('appointment.manage', ['appointment' => $appointment->token]))->with('cancelled', true);
    }

    public function ics(Appointment $appointment): Response
    {
        $appointment->load('type');
        $locale = app()->getLocale();
        $body = Ics::forAppointment(
            $appointment,
            __('leads.mail.ics_summary', ['type' => loc($appointment->type->name) ?? '']),
            Locale::route('appointment.manage', ['appointment' => $appointment->token]),
            AppointmentFormat::place($appointment, $locale),
            Settings::get('contact.email') ?: config('mail.from.address'),
            $appointment->status === Appointment::CANCELLED,
        );

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="afspraak-jebbari.ics"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** Slots in URLs are UTC "2026-11-20T09:30". */
    private function parseSlot(string $slot): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $slot)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d\TH:i', $slot, 'UTC')->startOfMinute();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rescheduling: ?verzet={token} of an upcoming appointment. */
    private function moving(Request $request): ?Appointment
    {
        $token = (string) $request->query('verzet', $request->input('verzet', ''));

        if (! preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            return null;
        }

        $appointment = Appointment::query()->where('token', $token)->with('lead', 'type')->first();

        return $appointment?->isUpcoming() ? $appointment : null;
    }

    private function keep(Request $request): array
    {
        return $this->moving($request) ? ['verzet' => $request->query('verzet', $request->input('verzet'))] : [];
    }
}
