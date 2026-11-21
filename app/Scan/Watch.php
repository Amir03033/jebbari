<?php

namespace App\Scan;

use App\Jobs\RunScan;
use App\Mail\WatchConfirm;
use App\Mail\WatchDown;
use App\Mail\WatchReport;
use App\Models\Scan;
use App\Models\ScanWatch;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Website watch: sign-up from a finished check, the monthly runs and the mail
 * after each run. The runs are ordinary scans (same checks, same SSRF guard),
 * started by `php artisan scan:watch` (scheduled daily) or, without a
 * scheduler, by the hourly tick in AppServiceProvider.
 */
class Watch
{
    /** Ask for a watch of the site in this scan. Returns the outcome key for the page. */
    public static function request(Scan $scan, string $ipHash): string
    {
        $existing = ScanWatch::query()->where('email', $scan->email)->where('domain', $scan->domain)->first();

        if ($existing?->confirmed_at) {
            return 'already';
        }

        $active = ScanWatch::query()->where('email', $scan->email)->whereNotNull('confirmed_at')->count();

        if ($active >= ScanWatch::MAX_PER_EMAIL) {
            return 'limit';
        }

        $watch = $existing ?? new ScanWatch(['email' => $scan->email, 'domain' => $scan->domain]);
        $watch->fill([
            'url' => $scan->url,
            'locale' => app()->getLocale(),
            'last_scores' => $scan->scores,
            'last_scan_id' => $scan->id,
            'ip_hash' => $ipHash,
        ])->save();

        self::send($watch->email, new WatchConfirm($watch));

        return 'sent';
    }

    public static function confirm(ScanWatch $watch): void
    {
        if (! $watch->confirmed_at) {
            $watch->update(['confirmed_at' => now(), 'next_run_at' => now()->addMonth()]);
        }
    }

    /** Start the checks that are due. Returns how many were started. */
    public static function runDue(int $limit = 25): int
    {
        $watches = ScanWatch::query()->due()->orderBy('next_run_at')->limit($limit)->get();

        foreach ($watches as $watch) {
            // Moved on first, so a slow or failing run is never started twice.
            $watch->update(['next_run_at' => now()->addMonth(), 'last_run_at' => now(), 'runs' => $watch->runs + 1]);

            $scan = Scan::create([
                'url' => $watch->url,
                'domain' => $watch->domain,
                'email' => $watch->email,
                'locale' => $watch->locale,
                'status' => Scan::PENDING,
                'ip_hash' => 'watch',
                'scan_watch_id' => $watch->id,
            ]);

            RunScan::dispatch($scan);
        }

        return $watches->count();
    }

    /** After a watch run: the comparison mail (or the "not reachable" mail), then remember the scores. */
    public static function finished(Scan $scan): void
    {
        $watch = $scan->watch;

        if (! $watch) {
            return;
        }

        if ($scan->status === Scan::DONE) {
            // A copy, so the mail keeps last month's scores even when it is rendered later.
            self::send($watch->email, new WatchReport(clone $watch, $scan));
            $watch->update(['last_scores' => $scan->scores, 'last_scan_id' => $scan->id]);
        } else {
            self::send($watch->email, new WatchDown($watch, $scan));
        }
    }

    private static function send(string $to, Mailable $mail): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
