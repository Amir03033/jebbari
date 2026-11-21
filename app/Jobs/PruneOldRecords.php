<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Models\UptimeCheck;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Deletes what the privacy statement says is deleted: old checks, request
 * drafts, leads and contact messages 12 months after the last contact, past
 * appointments, unconfirmed watches and old uptime measurements.
 * Scheduled daily; without a scheduler queued daily by the tick in AppServiceProvider.
 */
class PruneOldRecords implements ShouldQueue
{
    use Queueable;

    public const MODELS = [Scan::class, Lead::class, Inquiry::class, Appointment::class, ScanWatch::class, UptimeCheck::class];

    public function handle(): void
    {
        Artisan::call('model:prune', ['--model' => self::MODELS]);
    }
}
