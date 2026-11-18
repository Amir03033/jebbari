<?php

namespace App\Http\Controllers;

use App\Leads\LeadSession;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Scan\Watch;
use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Website watch: sign up from a report, confirm by email, unsubscribe by email. */
class ScanWatchController extends Controller
{
    public function store(Request $request, Scan $scan): RedirectResponse
    {
        $request->validate(['consent' => ['accepted']]);

        $outcome = $scan->status === Scan::DONE && ! $scan->scan_watch_id
            ? Watch::request($scan, LeadSession::ipHash($request))
            : 'already';

        return redirect(Locale::route('scan.show', ['scan' => $scan->token]).'#watch')->with('watch', $outcome);
    }

    public function confirm(ScanWatch $watch): Response
    {
        Watch::confirm($watch);
        app()->setLocale($watch->locale);

        return $this->page(__('scan.watch.confirmed_title'), __('scan.watch.confirmed_text', [
            'date' => $watch->next_run_at->locale($watch->locale)->translatedFormat('j F'),
            'domain' => $watch->domain,
        ]));
    }

    public function stop(ScanWatch $watch): Response
    {
        app()->setLocale($watch->locale);
        $domain = $watch->domain;
        $watch->delete();

        return $this->page(__('scan.watch.stopped_title'), __('scan.watch.stopped_text', ['domain' => $domain]));
    }

    private function page(string $title, string $text): Response
    {
        $seo = Seo::make(['title' => $title, 'canonical' => Locale::url('scan'), 'noindex' => true]);

        return response(view('pages.scan-watch', ['seo' => $seo, 'title' => $title, 'text' => $text]))
            ->header('Cache-Control', 'no-store, private');
    }
}
