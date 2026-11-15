<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Support\Locale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Cancellation: a short note to the visitor, or a plain notice to the owner. */
class AppointmentCancelled extends Mailable
{
    public function __construct(public Appointment $appointment, public bool $toOwner) {}

    public function envelope(): Envelope
    {
        $locale = $this->toOwner ? 'nl' : $this->appointment->lead->locale;

        return new Envelope(subject: __($this->toOwner ? 'leads.mail.owner_cancelled_subject' : 'leads.mail.cancelled_subject', ['when' => AppointmentFormat::when($this->appointment, $locale), 'name' => $this->appointment->lead->name], $locale));
    }

    public function content(): Content
    {
        $a = $this->appointment;
        $locale = $this->toOwner ? 'nl' : $a->lead->locale;

        return new Content(html: 'mail.brand', text: 'mail.brand-text', with: [
            'locale' => $locale,
            'label' => __('leads.appointment.label', [], $locale),
            'title' => __($this->toOwner ? 'leads.mail.owner_cancelled_title' : 'leads.mail.cancelled_title', ['name' => $a->lead->name], $locale),
            'paragraphs' => $this->toOwner ? [] : [__('leads.mail.cancelled_intro', [], $locale)],
            'rows' => AppointmentFormat::rows($a, $locale),
            'button' => $this->toOwner ? null : ['label' => __('leads.mail.book_again', [], $locale), 'url' => Locale::url('appointment', $locale)],
        ]);
    }
}
