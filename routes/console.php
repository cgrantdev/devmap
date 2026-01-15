<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic scraping based on frequency settings
Schedule::command('scraping:run-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
