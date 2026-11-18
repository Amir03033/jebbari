<?php

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Scan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Old scans, anonymous request drafts and long-past appointments. RunScan and LeadSession also prune, so it works without a scheduler.
Schedule::command('model:prune', ['--model' => [Scan::class, Lead::class, Appointment::class]])->daily();
