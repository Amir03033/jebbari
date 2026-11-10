<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmSubscription;
use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Newsletter sign-up with double opt-in: an address only counts after the
 * link in the confirmation email has been opened. Every email carries a
 * one-click unsubscribe link.
 */
class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $back = redirect(url()->previous().'#newsletter');

        // Honeypot: pretend it worked so a bot learns nothing.
        if (filled($request->input('website'))) {
            return $back->with('newsletter', 'sent');
        }

        $data = $request->validateWithBag('newsletter', ['newsletter_email' => ['required', 'email:rfc', 'max:190']], [], ['newsletter_email' => __('site.contact.attr_email')]);

        $subscriber = Subscriber::query()->firstOrNew(['email' => mb_strtolower(trim($data['newsletter_email']))]);

        // An address that is already confirmed gets the same answer: nothing about a subscription leaks.
        if (! $subscriber->exists || $subscriber->confirmed_at === null || $subscriber->unsubscribed_at !== null) {
            $subscriber->fill(['locale' => app()->getLocale(), 'unsubscribed_at' => null, 'confirmed_at' => null])->save();

            try {
                Mail::to($subscriber->email)->send(new ConfirmSubscription($subscriber));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $back->with('newsletter', 'sent');
    }

    public function confirm(Subscriber $subscriber): View
    {
        if ($subscriber->confirmed_at === null) {
            $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
        }

        return $this->message($subscriber, 'confirmed');
    }

    public function unsubscribe(Subscriber $subscriber): View
    {
        $subscriber->update(['unsubscribed_at' => $subscriber->unsubscribed_at ?? now()]);

        return $this->message($subscriber, 'unsubscribed');
    }

    private function message(Subscriber $subscriber, string $state): View
    {
        app()->setLocale(in_array($subscriber->locale, config('site.locales'), true) ? $subscriber->locale : config('site.default_locale'));

        return view('pages.newsletter', ['state' => $state]);
    }
}
