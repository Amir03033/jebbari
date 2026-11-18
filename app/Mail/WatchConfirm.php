<?php

namespace App\Mail;

use App\Models\ScanWatch;
use Illuminate\Support\Facades\URL;

/** Double opt-in for a website watch: the link turns it on. */
class WatchConfirm extends BrandMail
{
    public function __construct(public ScanWatch $watch) {}

    protected function mailLocale(): string
    {
        return $this->watch->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('scan.watch.mail.confirm_subject', ['domain' => $this->watch->domain]);
    }

    protected function parts(): array
    {
        return [
            'label' => $this->t('scan.watch.label').' · '.$this->watch->domain,
            'title' => $this->t('scan.watch.mail.confirm_title'),
            'paragraphs' => [$this->t('scan.watch.mail.confirm_text', ['domain' => $this->watch->domain])],
            'button' => [
                'label' => $this->t('scan.watch.mail.confirm_button'),
                'url' => URL::temporarySignedRoute('scan.watch.confirm', now()->addDays(7), ['watch' => $this->watch->id]),
            ],
        ];
    }
}
