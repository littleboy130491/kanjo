<?php

use App\Console\Commands\CheckOverdueDocuments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckOverdueDocuments::class)->daily();
Schedule::command('activity-log:prune 365')->dailyAt('00:00');
