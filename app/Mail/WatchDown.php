<?php

namespace App\Mail;

use App\Models\Scan;
use App\Models\ScanWatch;
use App\Support\Locale;
use Illuminate\Support\Facades\URL;

/** The monthly check could not load the site: say so, plainly. */
class WatchDown extends BrandMail
{
    public function __construct(public ScanWatch $watch, public Scan $scan) {}

    protected function mailLocale(): string
    {
        return $this->watch->locale;
    }

    protected function subjectLine(): string
    {
        return $this->t('scan.watch.mail.down_subject', ['domain' => $this->watch->domain]);
    }

    protected function parts(): array
    {
        $locale = $this->mailLocale();

        return [
            'label' => $this->t('scan.watch.label').' · '.$this->watch->domain,
            'title' => $this->t('scan.watch.mail.down_title', ['domain' => $this->watch->domain]),
            'paragraphs' => [$this->t('scan.watch.mail.down_text', ['reason' => $this->t('scan.error.'.($this->scan->error ?: 'internal'))])],
            'box' => [
                'text' => $this->t('scan.watch.mail.box'),
                'label' => $this->t('scan.watch.mail.box_link'),
                'url' => Locale::url('contact', $locale).'?'.http_build_query(['scan' => $this->scan->url]).'#contact-form',
            ],
            'footer' => $this->t('scan.watch.mail.footer', ['url' => URL::signedRoute('scan.watch.stop', ['watch' => $this->watch->id])]),
        ];
    }
}
