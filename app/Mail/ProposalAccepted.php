<?php

namespace App\Mail;

use App\Models\Proposal;

/** To the client: confirmation of their acceptance. */
class ProposalAccepted extends BrandMail
{
    public function __construct(public Proposal $proposal) {}

    protected function mailLocale(): string
    {
        return $this->proposal->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('proposal.mail.accepted_subject', ['title' => $this->proposal->title]);
    }

    protected function parts(): array
    {
        $p = $this->proposal;
        $locale = $this->mailLocale();

        return [
            'label' => $this->t('proposal.label'),
            'title' => $this->t('proposal.mail.accepted_title', ['name' => $p->accepted_name]),
            'paragraphs' => [$this->t('proposal.mail.accepted_intro')],
            'rows' => ProposalFormat::rows($p, $locale) + [
                $this->t('proposal.mail.accepted_on') => $p->accepted_at->timezone(config('site.timezone'))->locale($locale)->translatedFormat('j F Y, H:i'),
            ],
            'button' => ['label' => $this->t('proposal.mail.sent_button'), 'url' => $p->url()],
            'footer' => $this->t('proposal.mail.footer'),
        ];
    }
}
