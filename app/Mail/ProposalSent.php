<?php

namespace App\Mail;

use App\Models\Proposal;

/** To the client: the link to their proposal (sent from the admin). */
class ProposalSent extends BrandMail
{
    public function __construct(public Proposal $proposal) {}

    protected function mailLocale(): string
    {
        return $this->proposal->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('proposal.mail.sent_subject', ['title' => $this->proposal->title]);
    }

    protected function parts(): array
    {
        $p = $this->proposal;

        return [
            'label' => $this->t('proposal.label'),
            'title' => $this->t('proposal.mail.sent_title', ['name' => $p->client_name]),
            'paragraphs' => [$this->t('proposal.mail.sent_intro')],
            'rows' => ProposalFormat::rows($p, $this->mailLocale()),
            'button' => ['label' => $this->t('proposal.mail.sent_button'), 'url' => $p->url()],
            'footer' => $this->t('proposal.mail.footer'),
        ];
    }
}
