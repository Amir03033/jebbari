<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** To the owner: a client accepted or declined a proposal. Dutch, plain text, like NewLead. */
class ProposalDecision extends Mailable
{
    public function __construct(public Proposal $proposal) {}

    public function envelope(): Envelope
    {
        $verb = $this->proposal->accepted_at ? 'Akkoord' : 'Afgewezen';

        return new Envelope(
            subject: "$verb: {$this->proposal->title} ({$this->proposal->client_name})",
            replyTo: [$this->proposal->client_email],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.proposal-decision', with: ['p' => $this->proposal]);
    }
}
