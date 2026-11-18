<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeadPages;
use App\Http\Requests\StoreScanRequest;
use App\Jobs\RunScan;
use App\Models\Lead;
use App\Models\Scan;
use App\Scan\Turnstile;
use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The free website check: the form (/nl/scan), the request, and the status and
 * report page (/nl/scan/{token}). The scan itself runs in RunScan.
 */
class ScanController extends Controller
{
    use LeadPages;

    public function create(): View
    {
        $locale = app()->getLocale();
        $alternates = collect(Locale::all())->mapWithKeys(fn ($l) => [$l => Locale::url('scan', $l)])->all();

        return view('pages.scan', [
            'seo' => Seo::make([
                'title' => __('scan.page.title'),
                'description' => __('scan.page.description'),
                'canonical' => Locale::url('scan', $locale),
                'alternates' => $alternates,
            ]),
            'turnstile' => Turnstile::enabled() ? Turnstile::siteKey() : null,
        ]);
    }

    public function store(StoreScanRequest $request, Turnstile $turnstile): RedirectResponse
    {
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $back = redirect(Locale::url('scan').'#scan-form')->withInput($request->only('url', 'email'));

        if ($request->isBot()) {
            Log::info('Scan form honeypot triggered', ['ip_hash' => $ipHash]);

            return redirect(Locale::url('scan'));
        }

        if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return $back->withErrors(['url' => __('scan.form.turnstile')]);
        }

        $key = 'scan:'.$ipHash;

        if (RateLimiter::tooManyAttempts($key, (int) config('scan.daily_limit'))) {
            return $back->withErrors(['url' => __('scan.form.limit', ['count' => config('scan.daily_limit')])]);
        }

        RateLimiter::hit($key, 86400);

        $scan = Scan::create([
            'url' => $request->target['url'],
            'domain' => preg_replace('/^www\./', '', $request->target['host']),
            'email' => mb_strtolower(trim($request->string('email'))),
            'locale' => app()->getLocale(),
            'status' => Scan::PENDING,
            'ip_hash' => $ipHash,
            'contact_consent' => $request->boolean('contact_consent'),
        ]);

        // "May contact me" turns the check into a lead in the shared overview.
        if ($scan->contact_consent) {
            $lead = Lead::create([
                'source' => 'scan',
                'email' => $scan->email,
                'contact_consent' => true,
                'locale' => $scan->locale,
                'ip_hash' => $ipHash,
                'choices' => ['scan' => ['url' => $scan->url, 'report' => Locale::route('scan.show', ['scan' => $scan->token])]],
            ]);
            $this->notifyOwner($lead, 'scan');
        }

        // Without a queue worker (QUEUE_CONNECTION=sync) the scan runs after the response is sent.
        if (config('queue.default') === 'sync') {
            RunScan::dispatchAfterResponse($scan);
        } else {
            RunScan::dispatch($scan);
        }

        return redirect(Locale::route('scan.show', ['scan' => $scan->token]));
    }

    public function show(Scan $scan): Response
    {
        $view = view('pages.scan-report', [
            'scan' => $scan,
            'title' => $scan->status === Scan::DONE ? __('scan.report.title', ['domain' => $scan->domain]) : __('scan.status.title'),
        ]);

        // A report is private to whoever has the link: never cached or indexed.
        return response($view)->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Polled by the status page: only the state, never the results. */
    public function status(Scan $scan): JsonResponse
    {
        return response()->json(['status' => $scan->isStuck() ? Scan::FAILED : $scan->status])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
