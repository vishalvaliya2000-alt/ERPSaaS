<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily Cron: Clean previous follow-ups & scan live transactions across all active companies at 08:00 AM
Schedule::command('erp:generate-daily-actions')->dailyAt('08:00');

// Evening Daily Digest: 7:00 PM Executive Briefing on today's dispatches, collections & tomorrow's radar
Schedule::command('erp:send-daily-digest')->dailyAt('19:00');
