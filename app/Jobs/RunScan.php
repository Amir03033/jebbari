<?php

namespace App\Jobs;

use App\Mail\ScanReady;
use App\Models\Scan;
use App\Scan\FetchFailed;
use App\Scan\Scanner;
use App\Scan\UnsafeUrl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Runs one website check in the background and mails the report link. */
class RunScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Fetching, the extras and PageSpeed together stay well under this. */
    public int $timeout = 110;

    public function __construct(public Scan $scan) {}

    public function handle(Scanner $scanner): void
    {
        $scan = $this->scan->fresh();

        if (! $scan || $scan->isFinished()) {
            return;
        }

        $scan->update(['status' => Scan::RUNNING, 'started_at' => now()]);

        try {
            $result = $scanner->scan($scan->url);
            $scan->update([
                'status' => Scan::DONE,
                'url' => mb_substr($result['url'], 0, 2048),
                'scores' => $result['scores'],
                'results' => $result['results'],
                'finished_at' => now(),
            ]);
        } catch (UnsafeUrl|FetchFailed $e) {
            $scan->update(['status' => Scan::FAILED, 'error' => $e->reason, 'finished_at' => now()]);
        } catch (Throwable $e) {
            report($e);
            $scan->update(['status' => Scan::FAILED, 'error' => 'internal', 'finished_at' => now()]);
        }

        if ($scan->status === Scan::DONE) {
            try {
                Mail::to($scan->email)->send(new ScanReady($scan));
            } catch (Throwable $e) {
                report($e);
            }
        }

        // Retention without depending on a scheduler: old scans go when new ones run.
        Scan::query()->where('created_at', '<', now()->subDays(config('scan.retention_days')))->delete();
    }

    public function failed(?Throwable $e): void
    {
        $this->scan->fresh()?->update(['status' => Scan::FAILED, 'error' => $this->scan->error ?? 'internal', 'finished_at' => now()]);
    }
}
