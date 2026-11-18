<?php

namespace App\Jobs;

use App\Scan\Watch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Starts the website-watch checks that are due (see the tick in AppServiceProvider). */
class RunDueWatches implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Watch::runDue();
    }
}
