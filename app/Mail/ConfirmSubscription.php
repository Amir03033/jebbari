<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class ConfirmSubscription extends Mailable
{
    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('site.newsletter.mail_subject', [], $this->subscriber->locale));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.confirm-subscription', with: [
            'locale' => $this->subscriber->locale,
            'confirmUrl' => URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $this->subscriber->id]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber->id]),
        ]);
    }
}
