<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Http\Requests\StoreInquiryRequest;
use App\Mail\NewInquiry;
use App\Models\Inquiry;
use App\Support\Locale;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        $back = redirect(Locale::url('contact').'#contact-form')->with('inquiry_sent', true);

        // Honeypot: pretend it worked so the bot learns nothing, but store nothing.
        if ($request->isBot()) {
            Log::info('Contact form honeypot triggered', ['ip_hash' => $this->ipHash($request)]);

            return $back;
        }

        // Stored first, so an inquiry is never lost if mail fails.
        $inquiry = Inquiry::create([
            'name' => trim($request->string('name')),
            'email' => trim($request->string('email')),
            'company' => trim($request->string('company')) ?: null,
            'budget' => $request->input('budget') ?: null,
            'message' => trim($request->string('message')),
            'locale' => app()->getLocale(),
            'status' => InquiryStatus::New,
            'ip_hash' => $this->ipHash($request),
        ]);

        $this->notify($inquiry);

        return $back;
    }

    private function notify(Inquiry $inquiry): void
    {
        $to = Settings::get('contact.notify_email') ?: Settings::get('contact.email');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new NewInquiry($inquiry));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** A keyed hash, so repeat abuse can be spotted without storing the address itself. */
    private function ipHash(StoreInquiryRequest $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
