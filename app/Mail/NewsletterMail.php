<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\Subscriber;
use App\Support\HtmlSanitizer;
use App\Support\Locale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/** One newsletter to one subscriber, in the house style, with a one-click unsubscribe. */
class NewsletterMail extends Mailable
{
    public function __construct(public Newsletter $newsletter, public ?Subscriber $subscriber, public string $lang = 'nl') {}

    private function unsubscribeUrl(): ?string
    {
        return $this->subscriber ? URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber->id]) : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->newsletter->in($this->lang)['subject']);
    }

    public function headers(): Headers
    {
        $url = $this->unsubscribeUrl();

        return new Headers(text: $url ? ['List-Unsubscribe' => "<$url>"] : []);
    }

    public function content(): Content
    {
        $text = $this->newsletter->in($this->lang);
        $post = $this->newsletter->post;

        return new Content(html: 'mail.newsletter', text: 'mail.newsletter-text', with: [
            'locale' => $this->lang,
            'subject' => $text['subject'],
            'body' => HtmlSanitizer::clean($text['body']),
            'postUrl' => $post && $post->isPublished() && $post->slugFor($this->lang) ? Locale::route('blog.show', ['slug' => $post->slugFor($this->lang)], $this->lang) : null,
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ]);
    }
}
