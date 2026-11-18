<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A mail to a visitor in the house style (mail/brand.blade.php, with a plain
 * text twin). Subclasses fill in the parts; the copy comes from lang/leads.
 */
abstract class BrandMail extends Mailable
{
    abstract protected function mailLocale(): string;

    abstract protected function subjectLine(): string;

    /**
     * @return array{label: string, title: string, paragraphs?: list<string>, rows?: array<string, string>,
     *   button?: array{label: string, url: string}, box?: array{text: string, label: string, url: string}, footer?: string}
     */
    abstract protected function parts(): array;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(html: 'mail.brand', text: 'mail.brand-text', with: ['locale' => $this->mailLocale(), ...$this->parts()]);
    }

    protected function t(string $key, array $replace = []): string
    {
        return __($key, $replace, $this->mailLocale());
    }
}
