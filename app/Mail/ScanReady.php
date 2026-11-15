<?php

namespace App\Mail;

use App\Models\Scan;
use App\Support\Locale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Your website check is ready", with the report link and the scores. */
class ScanReady extends Mailable
{
    public function __construct(public Scan $scan) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('scan.mail.subject', ['domain' => $this->scan->domain], $this->scan->locale));
    }

    public function content(): Content
    {
        $locale = $this->scan->locale;

        return new Content(
            html: 'mail.scan-ready',
            text: 'mail.scan-ready-text',
            with: [
                'locale' => $locale,
                'reportUrl' => Locale::route('scan.show', ['scan' => $this->scan->token], $locale),
                'contactUrl' => Locale::url('contact', $locale).'?'.http_build_query(['scan' => $this->scan->url]).'#contact-form',
                'scores' => $this->scan->scores ?? [],
                'overall' => $this->scan->overall(),
            ],
        );
    }
}
