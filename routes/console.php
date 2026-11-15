<?php

use App\Models\Scan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Website checks are deleted after config('scan.retention_days'). RunScan also does this, so it works without a scheduler.
Schedule::command('model:prune', ['--model' => [Scan::class]])->daily();
