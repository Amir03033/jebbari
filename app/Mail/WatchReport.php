<?php

namespace App\Mail;

use App\Models\Scan;
use App\Models\ScanWatch;
use App\Support\Locale;
use Illuminate\Support\Facades\URL;

/** The monthly check: scores now, the change since last month, and a warning when it got worse. */
class WatchReport extends BrandMail
{
    public function __construct(public ScanWatch $watch, public Scan $scan) {}

    protected function mailLocale(): string
    {
        return $this->watch->locale;
    }

    private function worse(): bool
    {
        return $this->watch->isWorse($this->scan->scores ?? []);
    }

    protected function subjectLine(): string
    {
        $key = $this->worse() ? 'worse_subject' : 'report_subject';

        return $this->t("scan.watch.mail.$key", ['domain' => $this->watch->domain, 'score' => $this->scan->overall() ?? '-']);
    }

    protected function parts(): array
    {
        $locale = $this->mailLocale();
        $first = empty($this->watch->last_scores);
        $rows = [$this->t('scan.watch.mail.overall') => ($this->scan->overall() ?? '-').'/100'];

        foreach ($this->watch->compare($this->scan->scores ?? []) as $category => [$now, $was, $diff]) {
            $value = $now === null ? $this->t('scan.watch.mail.not_measured') : $now.'/100';
            if ($diff !== null && $diff !== 0) {
                $value .= ' ('.($diff > 0 ? '+' : '−').abs($diff).')';
            }
            $rows[__('scan.category.'.$category, [], $locale)] = $value;
        }

        $worse = $this->worse();

        return [
            'label' => $this->t('scan.watch.label').' · '.$this->watch->domain,
            'title' => $this->t($worse ? 'scan.watch.mail.worse_title' : 'scan.watch.mail.report_title', ['domain' => $this->watch->domain]),
            'paragraphs' => [$this->t($first ? 'scan.watch.mail.first_text' : ($worse ? 'scan.watch.mail.worse_text' : 'scan.watch.mail.report_text'))],
            'rows' => $rows,
            'button' => ['label' => $this->t('scan.watch.mail.button'), 'url' => Locale::route('scan.show', ['scan' => $this->scan->token], $locale)],
            'box' => $worse ? [
                'text' => $this->t('scan.watch.mail.box'),
                'label' => $this->t('scan.watch.mail.box_link'),
                'url' => Locale::url('contact', $locale).'?'.http_build_query(['scan' => $this->scan->url]).'#contact-form',
            ] : null,
            'footer' => $this->t('scan.watch.mail.footer', ['url' => URL::signedRoute('scan.watch.stop', ['watch' => $this->watch->id])]),
        ];
    }
}
