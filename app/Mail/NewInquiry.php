<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewInquiry extends Mailable
{
    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe aanvraag via de website: '.$this->inquiry->name,
            replyTo: [$this->inquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.new-inquiry');
    }
}
