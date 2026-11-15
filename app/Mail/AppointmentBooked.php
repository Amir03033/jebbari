<?php

namespace App\Mail;

use App\Leads\Ics;
use App\Models\Appointment;
use App\Support\Locale;
use App\Support\Settings;
use Illuminate\Mail\Mailables\Attachment;

/** Confirmation of a booked (or moved) appointment, with an .ics file and a link to change it. */
class AppointmentBooked extends BrandMail
{
    public function __construct(public Appointment $appointment, public bool $rescheduled = false) {}

    protected function mailLocale(): string
    {
        return $this->appointment->lead->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('leads.mail.booked_subject', ['when' => AppointmentFormat::when($this->appointment, $this->mailLocale())]);
    }

    protected function parts(): array
    {
        $a = $this->appointment;

        return [
            'label' => $this->t('leads.appointment.label'),
            'title' => $this->t($this->rescheduled ? 'leads.mail.moved_title' : 'leads.mail.booked_title'),
            'paragraphs' => [$this->t('leads.mail.booked_intro')],
            'rows' => AppointmentFormat::rows($a, $this->mailLocale()),
            'button' => ['label' => $this->t('leads.mail.manage'), 'url' => Locale::route('appointment.manage', ['appointment' => $a->token], $this->mailLocale())],
            'footer' => $this->t('leads.mail.ics_note'),
        ];
    }

    public function attachments(): array
    {
        $a = $this->appointment;
        $ics = Ics::forAppointment(
            $a,
            $this->t('leads.mail.ics_summary', ['type' => loc($a->type->name, $this->mailLocale()) ?? '']),
            Locale::route('appointment.manage', ['appointment' => $a->token], $this->mailLocale()),
            AppointmentFormat::place($a, $this->mailLocale()),
            Settings::get('contact.email') ?: config('mail.from.address'),
        );

        return [Attachment::fromData(fn () => $ics, 'afspraak-jebbari.ics')->withMime('text/calendar; charset=utf-8; method=PUBLISH')];
    }
}
