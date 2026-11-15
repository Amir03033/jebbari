<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the owner: a request, an appointment or an estimate came in. Dutch, plain text, like NewInquiry. */
class NewLead extends Mailable
{
    public function __construct(public Lead $lead, public string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('leads.owner.subject_'.$this->event, ['name' => $this->lead->name ?: $this->lead->email], 'nl'),
            replyTo: $this->lead->email ? [$this->lead->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.new-lead', with: ['lead' => $this->lead->load('appointments.type'), 'event' => $this->event]);
    }
}
