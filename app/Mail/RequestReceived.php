<?php

namespace App\Mail;

use App\Leads\PriceCalculator;
use App\Models\Lead;
use App\Support\Locale;

/** Confirmation to the visitor after the request wizard. */
class RequestReceived extends BrandMail
{
    public function __construct(public Lead $lead) {}

    protected function mailLocale(): string
    {
        return $this->lead->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('leads.mail.request_subject');
    }

    protected function parts(): array
    {
        return [
            'label' => $this->t('leads.request.label'),
            'title' => $this->t('leads.mail.request_title', ['name' => $this->lead->name]),
            'paragraphs' => [$this->t('leads.mail.request_intro')],
            'rows' => array_filter([
                $this->t('leads.request.summary') => mb_strimwidth((string) $this->lead->choice('wizard.summary'), 0, 120, '…'),
                $this->t('leads.price.estimate') => PriceCalculator::range($this->lead->estimate_min, $this->lead->estimate_max),
            ]),
            'box' => ['text' => $this->t('leads.mail.request_box'), 'label' => $this->t('leads.cta.appointment'), 'url' => Locale::url('appointment', $this->mailLocale())],
        ];
    }
}
