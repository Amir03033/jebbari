<?php

namespace App\Mail;

use App\Models\Preview;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PreviewApproved extends Mailable
{
    public function __construct(public Preview $preview) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Preview goedgekeurd: '.$this->preview->client_name);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.preview-approved');
    }
}
