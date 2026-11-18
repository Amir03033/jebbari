<?php

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Models\UptimeCheck;
use App\Scan\Watch;
use App\Uptime\Monitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Old scans, anonymous request drafts and long-past appointments. RunScan and LeadSession also prune, so it works without a scheduler.
Schedule::command('model:prune', ['--model' => [Scan::class, Lead::class, Appointment::class, ScanWatch::class, UptimeCheck::class]])->daily();

// Website watch: start the monthly checks that are due. Without a scheduler the
// hourly tick in AppServiceProvider does the same (docs/SCAN.md, Website watch).
Artisan::command('scan:watch', function () {
    $this->info(Watch::runDue().' website check(s) started.');
})->purpose('Start the monthly website-watch checks that are due');

Schedule::command('scan:watch')->hourly();

// Live uptime of monitored projects (docs/UPTIME.md). Without a scheduler the
// tick in AppServiceProvider measures at most every 10 minutes while there is traffic.
Artisan::command('uptime:check', function (Monitor $monitor) {
    $this->info($monitor->checkAll().' project(s) checked.');
})->purpose('Measure the monitored live projects once');

Schedule::command('uptime:check')->everyFiveMinutes();
