<?php

namespace App\Mail;

use App\Leads\PriceCalculator;
use App\Models\Lead;
use App\Support\Locale;

/** The calculator result, sent on request. */
class EstimateMail extends BrandMail
{
    public function __construct(public Lead $lead, public array $lines) {}

    protected function mailLocale(): string
    {
        return $this->lead->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('leads.mail.estimate_subject');
    }

    protected function parts(): array
    {
        $rows = [];
        foreach ($this->lines as $line) {
            $rows[$line['name']] = PriceCalculator::range($line['min'], $line['max']);
        }
        $rows[$this->t('leads.price.total')] = PriceCalculator::range($this->lead->estimate_min, $this->lead->estimate_max);

        return [
            'label' => $this->t('leads.price.label'),
            'title' => $this->t('leads.mail.estimate_title', ['range' => PriceCalculator::range($this->lead->estimate_min, $this->lead->estimate_max)]),
            'paragraphs' => [$this->t('leads.price.disclaimer')],
            'rows' => $rows,
            'button' => ['label' => $this->t('leads.cta.request'), 'url' => Locale::route('request.resume', ['lead' => $this->lead->token], $this->mailLocale())],
            'box' => ['text' => $this->t('leads.mail.estimate_box'), 'label' => $this->t('leads.cta.appointment'), 'url' => Locale::url('appointment', $this->mailLocale())],
        ];
    }
}
