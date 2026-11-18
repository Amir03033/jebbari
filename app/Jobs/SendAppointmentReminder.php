<?php

namespace App\Jobs;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Dispatched with a delay when an appointment is booked, so no scheduler is
 * needed. Does nothing when the appointment was cancelled, moved or reminded.
 */
class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Appointment $appointment) {}

    public function handle(): void
    {
        $a = $this->appointment->fresh(['lead', 'type']);

        if (! $a || ! $a->isUpcoming() || $a->reminder_sent_at !== null || ! $a->lead?->email) {
            return;
        }

        // Moved to a later time after this job was queued: that booking queued its own reminder.
        if ($a->starts_at->copy()->subHours((int) config('leads.reminder_hours') + 1)->isFuture()) {
            return;
        }

        Mail::to($a->lead->email)->send(new AppointmentReminder($a));
        $a->update(['reminder_sent_at' => now()]);
    }
}
