<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\NewLead;
use App\Models\Lead;
use App\Support\Locale;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Shared bits of the calculator, request and appointment controllers. */
trait LeadPages
{
    /** SEO for a lead page; personal pages (tokens) are not indexed. */
    protected function seo(string $key, string $route, bool $indexable = true): array
    {
        return Seo::make([
            'title' => __("leads.{$key}.title"),
            'description' => __("leads.{$key}.description"),
            'canonical' => Locale::url($route),
            'alternates' => collect(Locale::all())->mapWithKeys(fn ($l) => [$l => Locale::url($route, $l)])->all(),
            'noindex' => ! $indexable,
        ]);
    }

    protected function notifyOwner(Lead $lead, string $event): void
    {
        $this->mailVisitor(Settings::get('contact.notify_email') ?: Settings::get('contact.email'), new NewLead($lead, $event));
    }

    protected function mailVisitor(?string $to, $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
