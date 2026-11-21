<?php

namespace App\Http\Controllers;

use App\Mail\PreviewApproved;
use App\Mail\PreviewChangesRequested;
use App\Models\Preview;
use App\Support\Seo;
use App\Support\Settings;
use App\Support\ShareImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * The client's page for a preview: /review/{token}. The long random token is the
 * only key. Opening it sets the cookie the preview subdomain checks, shows the
 * site in a frame and lets the client approve or ask for changes.
 */
class PreviewReviewController extends Controller
{
    public function show(string $token): Response
    {
        $preview = $this->find($token);

        if (! $preview->isViewable()) {
            return response()->view('pages.review-unavailable', ['expired' => $preview->isExpired()], 410);
        }

        $response = response()->view('pages.review', [
            'preview' => $preview,
            'url' => config('preview.url').'/',
        ]);

        $minutes = max(1, (int) ceil(now()->diffInMinutes($preview->expires_at ?? now()->addDays(config('preview.lifetime_days')), true)));

        return $response->withCookie(cookie(
            config('preview.cookie'), $preview->access_key, $minutes, '/', config('preview.cookie_domain') ?: null,
            secure: str_starts_with((string) config('preview.url'), 'https://'), httpOnly: true, raw: true, sameSite: 'lax',
        ));
    }

    /** The image in the link preview when the link is shared (WhatsApp, iMessage, mail). */
    public function shareImage(string $token): BinaryFileResponse|RedirectResponse
    {
        $preview = $this->find($token);

        $path = $preview->isViewable()
            ? ShareImage::for('review-'.$preview->getKey().'-'.app()->getLocale(), __('site.review.share_image_title', ['name' => $preview->client_name]), __('site.review.share_label'))
            : null;

        return $path
            ? response()->file($path, ['Content-Type' => 'image/png'])
            : redirect(asset(Seo::DEFAULT_IMAGE));
    }

    public function approve(string $token): RedirectResponse
    {
        $preview = $this->findViewable($token);

        if (! $preview->approved_at) {
            $preview->forceFill(['approved_at' => now()])->saveQuietly();
            $this->notify(new PreviewApproved($preview));
        }

        return redirect()->route('review.show', $token)->with('review_sent', 'approved');
    }

    public function changes(Request $request, string $token): RedirectResponse
    {
        $preview = $this->findViewable($token);

        $data = $request->validate(['change_request' => ['required', 'string', 'min:3', 'max:5000']], [], [
            'change_request' => __('site.review.changes_label'),
        ]);

        $preview->forceFill([
            'change_request' => trim($data['change_request']),
            'change_requested_at' => now(),
            'approved_at' => null,
        ])->saveQuietly();

        $this->notify(new PreviewChangesRequested($preview));

        return redirect()->route('review.show', $token)->with('review_sent', 'changes');
    }

    private function find(string $token): Preview
    {
        $preview = Preview::query()->where('token', $token)->first();

        abort_if(! $preview || ! hash_equals($preview->token, $token), 404);

        app()->setLocale(in_array($preview->locale, config('site.locales'), true) ? $preview->locale : config('site.default_locale'));

        return $preview;
    }

    private function findViewable(string $token): Preview
    {
        $preview = $this->find($token);

        abort_unless($preview->isViewable(), 410);

        return $preview;
    }

    private function notify(Mailable $mail): void
    {
        $to = Settings::get('contact.notify_email') ?: Settings::get('contact.email');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
