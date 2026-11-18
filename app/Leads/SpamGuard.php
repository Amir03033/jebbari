<?php

namespace App\Leads;

use App\Http\Requests\StoreInquiryRequest;
use App\Scan\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * The same protection as the contact form, for the final step of each lead
 * form: honeypot (silent), minimum fill-in time, and Turnstile when enabled.
 */
class SpamGuard
{
    public function __construct(private Turnstile $turnstile) {}

    public static function stamp(): string
    {
        return Crypt::encryptString((string) time());
    }

    public static function isBot(Request $request): bool
    {
        return filled($request->input('website'));
    }

    /** @throws ValidationException */
    public function check(Request $request, string $field): void
    {
        if (! StoreInquiryRequest::plausibleTime((string) $request->input('_t'))) {
            throw ValidationException::withMessages([$field => __('site.contact.too_fast')]);
        }

        if (! $this->turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            throw ValidationException::withMessages([$field => __('leads.form.turnstile')]);
        }
    }
}
