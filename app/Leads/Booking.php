<?php

namespace App\Leads;

use App\Jobs\SendAppointmentReminder;
use App\Mail\AppointmentBooked;
use App\Mail\AppointmentCancelled;
use App\Mail\NewLead;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Lead;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Booking, rescheduling and cancelling. Double bookings are stopped twice:
 * the slot is checked again inside a transaction, and a unique index on the
 * start time rejects a second booking that slips through at the same moment.
 */
class Booking
{
    public function __construct(private SlotFinder $slots) {}

    /** @throws SlotTaken */
    public function book(Lead $lead, AppointmentType $type, CarbonImmutable $start, ?string $message = null, ?Appointment $replaces = null): Appointment
    {
        try {
            $appointment = DB::transaction(function () use ($lead, $type, $start, $message, $replaces) {
                if ($replaces) {
                    $this->release($replaces);
                }

                if (! $this->slots->isFree($type, $start)) {
                    throw new SlotTaken;
                }

                return Appointment::create([
                    'lead_id' => $lead->id,
                    'appointment_type_id' => $type->id,
                    'starts_at' => $start->utc(),
                    'ends_at' => $start->addMinutes($type->duration_minutes)->utc(),
                    'status' => Appointment::CONFIRMED,
                    'slot_key' => Appointment::slotKey($start),
                    'message' => $message,
                ]);
            });
        } catch (QueryException $e) {
            // The unique index: someone booked this exact moment at the same time.
            throw new SlotTaken(previous: $e);
        }

        $this->afterBooking($appointment, $replaces !== null);

        return $appointment;
    }

    public function cancel(Appointment $appointment): void
    {
        if ($appointment->status !== Appointment::CONFIRMED) {
            return;
        }

        $this->release($appointment);
        $this->mail($appointment->lead->email, new AppointmentCancelled($appointment, false));
        $this->mail($this->owner(), new AppointmentCancelled($appointment, true));
    }

    private function release(Appointment $appointment): void
    {
        $appointment->update(['status' => Appointment::CANCELLED, 'slot_key' => null]);
    }

    private function afterBooking(Appointment $appointment, bool $rescheduled): void
    {
        $appointment->load('lead', 'type');
        $this->mail($appointment->lead->email, new AppointmentBooked($appointment, $rescheduled));
        $this->mail($this->owner(), new NewLead($appointment->lead, $rescheduled ? 'appointment_moved' : 'appointment'));

        $remindAt = $appointment->starts_at->copy()->subHours((int) config('leads.reminder_hours'));

        if ($remindAt->isFuture()) {
            SendAppointmentReminder::dispatch($appointment)->delay($remindAt);
        }
    }

    private function owner(): ?string
    {
        return Settings::get('contact.notify_email') ?: Settings::get('contact.email');
    }

    private function mail(?string $to, $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
