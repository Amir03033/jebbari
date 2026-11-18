<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Support\Locale;

/** Reminder, a day before. */
class AppointmentReminder extends BrandMail
{
    public function __construct(public Appointment $appointment) {}

    protected function mailLocale(): string
    {
        return $this->appointment->lead->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('leads.mail.reminder_subject', ['when' => AppointmentFormat::when($this->appointment, $this->mailLocale())]);
    }

    protected function parts(): array
    {
        return [
            'label' => $this->t('leads.appointment.label'),
            'title' => $this->t('leads.mail.reminder_title'),
            'rows' => AppointmentFormat::rows($this->appointment, $this->mailLocale()),
            'button' => ['label' => $this->t('leads.mail.manage'), 'url' => Locale::route('appointment.manage', ['appointment' => $this->appointment->token], $this->mailLocale())],
        ];
    }
}
