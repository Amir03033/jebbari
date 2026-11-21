<?php

namespace App\Jobs;

use App\Uptime\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Measures the monitored projects once (see the tick in AppServiceProvider). */
class CheckUptime implements ShouldQueue
{
    use Queueable;

    public int $timeout = 50;

    public function handle(Monitor $monitor): void
    {
        $monitor->checkAll();
    }
}
